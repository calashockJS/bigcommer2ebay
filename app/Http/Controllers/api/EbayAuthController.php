<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class EbayAuthController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $tokenFile = '';
    private $scopes = 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.inventory';
    private $ebayEnvType;

    private $ebayUsername;
    private $ebayPassword;

    public function __construct()
    {
        $this->clientId = 'LuigiMoc-EcodatIm-SBX-4fce02210-06f07af6'; //env('EBAY_SANDBOX_CLIENT_ID');
        $this->clientSecret = 'SBX-debd9abe7fbe-5a31-4c41-b0a9-c494'; //env('EBAY_SANDBOX_CLIENT_SECRET');
        $this->redirectUri = 'https://bigcommer2ebay.onrender.com/api/ebay/callback';//env('EBAY_SANDBOX_REDIRECT_URI');
        //$this->redirectUri = 'https://big-com-ebay-data-migrate.test/api/ebay/callback';//env('EBAY_SANDBOX_REDIRECT_URI');

        $this->ebayUsername = 'testuser_judhisahoo';//env('EBAY_USERNAME');
        $this->ebayPassword = 'Jswecom*312#';//env('EBAY_PASSWORD');

        // Get environment type value from .env
        $envTypeEbay = '.sandbox.';//env('EBAY_ENV_TYPE');
        $this->ebayEnvType = $envTypeEbay;
        
        if ($envTypeEbay == '.sandbox.') {
            $this->tokenFile = 'ebay_sandbox_user_token.txt';
        }else{
            $this->tokenFile = 'ebay_user_token.txt';
        }
    }

    /**
     * Automated headless browser authentication for eBay
     */
    public function automatedEbayAuth()
    {
        // Check if we already have a valid token
        $storedToken = $this->readStoredToken();
        if ($storedToken && !$this->isTokenExpired($storedToken)) {
            return response()->json([
                'message' => 'Using existing token',
                'access_token' => $storedToken['access_token']
            ]);
        }

        // Try to refresh token if exists
        if ($storedToken && isset($storedToken['refresh_token'])) {
            $newToken = $this->refreshUserToken($storedToken['refresh_token']);
            if ($newToken) {
                $this->storeToken($newToken);
                return response()->json([
                    'message' => 'Token refreshed successfully',
                    'access_token' => $newToken['access_token']
                ]);
            }
        }

        // No valid token, initiate headless browser auth
        try {
            // Generate a state parameter for security
            $state = bin2hex(random_bytes(16));
            
            // Build the auth URL
            $authUrl = "https://auth".$this->ebayEnvType."ebay.com/oauth2/authorize?client_id={$this->clientId}"
                . "&redirect_uri=" . urlencode($this->redirectUri)
                . "&response_type=code"
                . "&scope=" . urlencode($this->scopes)
                . "&state={$state}";
            // Execute the Node.js script to handle automated browser login
            $scriptPath = base_path('node_scripts/ebay_auth.js');
            
            $process = new Process([
                'node', 
                $scriptPath, 
                $authUrl, 
                $this->ebayUsername, 
                $this->ebayPassword,
                $state
            ]);
            
            $process->setTimeout(60); // Set timeout to 60 seconds
            $process->run();
            
            if (!$process->isSuccessful()) {
                //echo '  failed exception   ';
                throw new ProcessFailedException($process);
            }
            $output = json_decode($process->getOutput(), true);
            
            if (isset($output['error'])) {
               // echo '$output["error"] '.$output['error'];
                return response()->json(['error' => $output['error']], 400);
            }
            
            if (!isset($output['code']) || !isset($output['state'])) {
                //echo 'not getting code or state, so Invalid response from authentication process';
                return response()->json(['error' => 'Invalid response from authentication process'], 400);
            }
            
            // Verify state parameter
            if ($output['state'] !== $state) {
                //echo '$output["state"] is not matching with '.$state;
                return response()->json(['error' => 'State mismatch, possible CSRF attack'], 400);
            }
            // Exchange the code for a token
            $tokenData = $this->exchangeCodeForToken($output['code']);
            if ($tokenData) {
                $this->storeToken($tokenData);
                return response()->json([
                    'message' => 'Authentication successful and token stored',
                    'access_token' => $tokenData['access_token']
                ]);
            }
            
            return response()->json(['error' => 'Failed to exchange code for token'], 500);
            
        } catch (\Exception $e) {
            Log::error('eBay automated auth error: ' . $e->getMessage());
            return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Redirect user to eBay Sandbox login for authorization.
     */
    public function redirectToEbay()
    {
        $state = bin2hex(random_bytes(16)); // CSRF protection
        session(['ebay_oauth_state' => $state]);

        $authUrl = "https://auth".$this->ebayEnvType."ebay.com/oauth2/authorize?client_id={$this->clientId}"
            . "&redirect_uri=" . urlencode($this->redirectUri)
            . "&response_type=code"
            . "&scope=" . urlencode($this->scopes)
            . "&state={$state}";
        
        return redirect()->away($authUrl);
    }

    /**
     * Handle eBay's callback and exchange authorization code for access token.
     */
    public function handleEbayCallback(Request $request)
    {
        if ($request->has('error')) {
            return response()->json(['error' => $request->get('error_description')], 400);
        }

        // Retrieve stored state from session
        $storedState = session('ebay_oauth_state');

        // Debugging: Log the stored and returned state values
        Log::channel('stderr')->info('Stored state: ' . $storedState);
        Log::channel('stderr')->info('Returned state: ' . $request->get('state'));

        if (!$storedState || $request->get('state') !== $storedState) {
            return response()->json(['error' => 'Invalid state'], 400);
        }

        // Remove the state from session after use
        session()->forget('ebay_oauth_state');

        $code = $request->get('code');

        $tokenData = $this->exchangeCodeForToken($code);

        if ($tokenData) {
            $this->storeToken($tokenData);
            //return response()->json(['message' => 'Token stored successfully', 'token' => $tokenData]);
            return response()->json(['message' => 'Token stored successfully']);
            //return redirect('/bigcommerce/show-bc-sku')->with(['msg' => 'Token stored successfully.']);
        }

        return response()->json(['error' => 'Failed to retrieve access token'], 500);
    }


    /**
     * Exchange authorization code for an access token.
     */
    private function exchangeCodeForToken($code)
    {
        Log::channel('stderr')->info( 'now at exchangeCodeForToken()');
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ])->asForm()->post('https://api'.$this->ebayEnvType.'ebay.com/identity/v1/oauth2/token', [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $this->redirectUri,
        ]);
        Log::channel('stderr')->info( 'now got $response ::'.json_encode($response->json()));
        if ($response->successful()) {
            $data = $response->json();
            return [
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at'    => time() + $data['expires_in']
            ];
            //return $response->json();
        }

        return null;
    }

    /**
     * Get a valid eBay Sandbox user access token.
     */
    //public function getUserAccessToken()
    public function getAppAccessToken()
    {
        //echo 'comming to getAppAccessToken() ';
        $storedToken = $this->readStoredToken();

        if ($storedToken && !$this->isTokenExpired($storedToken)) {
            //echo 'comming to collect access token and return back ';
            return response()->json(['access_token' => $storedToken['access_token']]);
        } else if ($storedToken && isset($storedToken['refresh_token'])) {
            $newToken = $this->refreshUserToken($storedToken['refresh_token']);

            if ($newToken) {
                $this->storeToken($newToken);
                return response()->json(['access_token' => $newToken['access_token']]);
            }
        } else {
            $this->redirectToEbay();
        }
        // Token expired or not found, get a new one
        //echo 'comming to call getAppAccessToken() ';
        /*$newToken = $this->fetchNewAppToken();
        
        if ($newToken) {
            echo 'now got recent token $newToken ::'.json_encode($newToken);
            $this->storeToken($newToken);
            return response()->json(['access_token' => $newToken['access_token']]);
        }
        //echo 'no $newToken.. so calling  to automateEbayLogin() ';
        $this->automateEbayLogin();

        return response()->json(['error' => 'No valid token. Please authorize again.'], 401);*/
        
    }

    private function automateEbayLogin()
    {
        $response = Http::get(url('/api/ebay/auth'));
        Log::channel('stderr')->info('Automated login triggered.', ['response' => $response->json()]);
    }

    /**
     * Fetch a new application access token (client credentials).
     */
    public function fetchNewAppToken()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ])->asForm()->post('https://api'.$this->ebayEnvType.'ebay.com/identity/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope'      => $this->scopes
        ]);

        Log::channel('stderr')->info('fetchNewAppToken ::',[$response->json()]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'access_token' => $data['access_token'],
                'expires_at'   => time() + $data['expires_in']
            ];
        }

        return null;
    }

    /**
     * Refresh the user access token.
     */
    private function refreshUserToken($refreshToken)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ])->asForm()->post('https://api'.$this->ebayEnvType.'ebay.com/identity/v1/oauth2/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope'         => $this->scopes
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at'    => time() + $data['expires_in']
            ];
        }

        return null;
    }

    /**
     * Read stored token from text file.
     */
    private function readStoredToken()
    {
        if (!Storage::exists($this->tokenFile)) {
            return null;
        }

        $tokenData = json_decode(Storage::get($this->tokenFile), true);

        return $tokenData ?: null;
    }

    /**
     * Check if the stored token is expired.
     */
    private function isTokenExpired($tokenData)
    {
        return !isset($tokenData['expires_at']) || time() >= $tokenData['expires_at'];
    }

    /**
     * Store the access token in a text file.
     */
    private function storeToken($tokenData)
    {
        Storage::put($this->tokenFile, json_encode($tokenData));
    }

    public function automatedEbayLogin()
    {
        Log::channel('stderr')->info( 'now at automatedEbayLogin() and going to call  getAuthorizationCode()');
        $authorizationCode = $this->getAuthorizationCode();
        Log::channel('stderr')->info('now got $authorizationCode ::'.$authorizationCode);
        if (!$authorizationCode) {
            Log::channel('stderr')->info('echo no...Failed to get authorization code');
            Log::channel('stderr')->info('Failed to get authorization code');
            return response()->json(['error' => 'Failed to get authorization code'], 401);
        }
        Log::channel('stderr')->info('now going to call exchangeCodeForToken()::');
        $accessToken = $this->exchangeCodeForToken($authorizationCode);
        Log::channel('stderr')->info('now got $accessToken ::'.json_encode($authorizationCode));
        if ($accessToken) {
            Log::channel('stderr')->info('now going to stora token and return access token ::'.$accessToken['access_token']);
            $this->storeToken($accessToken);
            return response()->json(['access_token' => $accessToken['access_token']]);
        }

        return response()->json(['error' => 'Failed to obtain access token'], 401);
    }


    private function getAuthorizationCode()
    {
        $authUrl = "https://auth".$this->ebayEnvType."ebay.com/oauth2/authorize?client_id={$this->clientId}&redirect_uri=" . urlencode($this->redirectUri) . "&response_type=code&scope=" . urlencode($this->scopes);

        $response = Http::withBasicAuth($this->ebayUsername, $this->ebayPassword)->get($authUrl);
        Log::channel('stderr')->info( '$response :: '.json_encode($response));
        if ($response->successful() && isset($response['code'])) {
            return $response['code'];
        }

        return null;
    }

    
}
