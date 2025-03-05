<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class EbayWebAuthController extends Controller
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
        $this->redirectUri = 'https://bigcommer2ebay-g6h6.onrender.com/api/ebay/callback';//env('EBAY_SANDBOX_REDIRECT_URI');

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
            return  array('type'=>'fail','message'=>$request->get('error_description'));
        }

        // Retrieve stored state from session
        $storedState = session('ebay_oauth_state');

        // Debugging: Log the stored and returned state values
        Log::info('Stored state: ' . $storedState);
        Log::info('Returned state: ' . $request->get('state'));

        if (!$storedState || $request->get('state') !== $storedState) {
            return array('type'=>'fail','message'=>'Invalid state');
        }

        // Remove the state from session after use
        session()->forget('ebay_oauth_state');

        $code = $request->get('code');

        $tokenData = $this->exchangeCodeForToken($code);

        if ($tokenData) {
            $this->storeToken($tokenData);
            //return response()->json(['message' => 'Token stored successfully', 'token' => $tokenData]);
            //return array('type'=>'successs','message' => 'Token stored successfully');
            return Redirect::back()->with(['msg' => 'Token stored successfully.']);
        }

        return array('type'=>'fail','message'=> 'Failed to retrieve access token');
    }


    /**
     * Exchange authorization code for an access token.
     */
    private function exchangeCodeForToken($code)
    {
        //echo 'now at exchangeCodeForToken()';
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ])->asForm()->post('https://api'.$this->ebayEnvType.'ebay.com/identity/v1/oauth2/token', [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $this->redirectUri,
        ]);
        //echo 'now got $response ::'.json_encode($response->json());
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
}
