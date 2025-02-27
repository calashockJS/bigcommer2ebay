<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class EbayAuthMiddleware
{
    private $tokenFile;
    private $accessToken;
    private $ebayEnvType;

    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $scopes = 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.inventory';

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
        $envTypeEbay = env('EBAY_ENV_TYPE');
        $this->ebayEnvType = $envTypeEbay;

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
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ebayAccessToken = $this->getUpdateAccessToken();
        if (empty($ebayAccessToken)) {
            return redirect('/api/ebay/auth'); // Redirect if no token
        }
        return $next($request);
    }

    private function getUpdateAccessToken(){
        $ebayAccessToken = '';
        Log::channel('stderr')->info('now in getUpdateAccessToken');
        Log::channel('stderr')->info('now going to calling readStoredToken()');
        // Check if we already have a valid token
        $storedToken = $this->readStoredToken();
        Log::channel('stderr')->info('$storedToken ::'.json_encode($storedToken));
        if ($storedToken && !$this->isTokenExpired($storedToken)) {
            $ebayAccessToken = $storedToken['access_token'];
            Log::channel('stderr')->info(' token  not expired $ebayAccessToken ::'.$ebayAccessToken);
        }else if ($storedToken && isset($storedToken['refresh_token'])) {
            Log::channel('stderr')->info('token expired. so going to call refresh token');
            Log::channel('stderr')->info('going to call refreshUserToken()');
            // Try to refresh token if exists
            $newToken = $this->refreshUserToken($storedToken['refresh_token']);
            Log::channel('stderr')->info('$newToken ::'.json_encode($newToken));
            if ($newToken) {
                Log::channel('stderr')->info(' calling storeToken()');
                $this->storeToken($newToken);
                $ebayAccessToken = $newToken['access_token'];
                Log::channel('stderr')->info('token  not expired $ebayAccessToken ::'.$ebayAccessToken);
            }
        }
        
        
        //echo '$ebayAccessToken ::'.$ebayAccessToken;
        if (!$ebayAccessToken) {
            Log::channel('stderr')->info( 'now in constructure $ebayAccessToken::'.$ebayAccessToken);
        }
        return $ebayAccessToken;
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
}
