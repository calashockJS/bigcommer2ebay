<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class EbayAuthController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $tokenFile = '';
    private $scopes = 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.inventory';
    private $ebayEnvType;

    public function __construct()
    {
        $this->clientId = env('EBAY_SANDBOX_CLIENT_ID');
        $this->clientSecret = env('EBAY_SANDBOX_CLIENT_SECRET');
        $this->redirectUri = env('EBAY_SANDBOX_REDIRECT_URI');

        // Get environment type value from .env
        $envTypeEbay = env('EBAY_ENV_TYPE');
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
            return response()->json(['error' => $request->get('error_description')], 400);
        }

        // Retrieve stored state from session
        $storedState = session('ebay_oauth_state');

        // Debugging: Log the stored and returned state values
        Log::info('Stored state: ' . $storedState);
        Log::info('Returned state: ' . $request->get('state'));

        if (!$storedState || $request->get('state') !== $storedState) {
            return response()->json(['error' => 'Invalid state'], 400);
        }

        // Remove the state from session after use
        session()->forget('ebay_oauth_state');

        $code = $request->get('code');

        $tokenData = $this->exchangeCodeForToken($code);

        if ($tokenData) {
            $this->storeToken($tokenData);
            return response()->json(['message' => 'Token stored successfully', 'token' => $tokenData]);
        }

        return response()->json(['error' => 'Failed to retrieve access token'], 500);
    }


    /**
     * Exchange authorization code for an access token.
     */
    private function exchangeCodeForToken($code)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ])->asForm()->post('https://api'.$this->ebayEnvType.'ebay.com/identity/v1/oauth2/token', [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $this->redirectUri,
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
     * Get a valid eBay Sandbox user access token.
     */
    //public function getUserAccessToken()
    public function getAppAccessToken()
    {
        $storedToken = $this->readStoredToken();

        if ($storedToken && !$this->isTokenExpired($storedToken)) {
            return response()->json(['access_token' => $storedToken['access_token']]);
        }

        /*if ($storedToken && isset($storedToken['refresh_token'])) {
            $newToken = $this->refreshUserToken($storedToken['refresh_token']);

            if ($newToken) {
                $this->storeToken($newToken);
                return response()->json(['access_token' => $newToken['access_token']]);
            }
        }*/
        // Token expired or not found, get a new one
        $newToken = $this->fetchNewAppToken();

        if ($newToken) {
            $this->storeToken($newToken);
            return response()->json(['access_token' => $newToken['access_token']]);
        }

        return response()->json(['error' => 'No valid token. Please authorize again.'], 401);
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

        Log::info('fetchNewAppToken ::',[$response->json()]);

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
}
