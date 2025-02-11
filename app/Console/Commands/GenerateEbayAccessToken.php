<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateEbayAccessToken extends Command
{
    protected $signature = 'ebay:generate-access-token';
    protected $description = 'Generate eBay Access Token via OAuth automatically (Sandbox Mode)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Step 1: Get Credentials from .env
        $clientId = env('EBAY_CLIENT_ID');
        $clientSecret = env('EBAY_CLIENT_SECREATE');
        $redirectUri = env('EBAY_REDIRECT_URI');
        $ebayEnvType = env('EBAY_ENV_TYPE');
        $scope = ($ebayEnvType === 'sandbox.') ? env('EBAY_SCOPE_SANDBOX') : env('EBAY_SCOPE');

        if (!$clientId || !$clientSecret || !$redirectUri) {
            $this->error("❌ Missing eBay API credentials in .env file!");
            return;
        }

        // Step 2: Generate Authorization URL
        $authUrl = "https://auth." . $ebayEnvType . "ebay.com/oauth2/authorize?"
            . "client_id={$clientId}"
            . "&redirect_uri={$redirectUri}"
            . "&response_type=code"
            . "&scope={$scope}";

        $this->info("🔄 Open the following URL in your browser and log in to eBay:");
        $this->line($authUrl);

        // Step 3: Prompt user to manually enter Authorization Code from redirect URL
        $authCode = $this->ask("🔑 Copy and paste the Authorization Code from eBay:");

        if (!$authCode) {
            $this->error("❌ No Authorization Code provided!");
            Log::error('Failed to retrieve Authorization Code.');
            return;
        }

        $this->info("✅ Authorization Code Retrieved: $authCode");

        // Step 4: Exchange Authorization Code for Access Token
        $this->info("🔄 Requesting Access Token from eBay...");

        $authHeader = base64_encode("$clientId:$clientSecret");

        $tokenUrl = "https://api." . $ebayEnvType . "ebay.com/identity/v1/oauth2/token";

        $response = Http::withHeaders([
            'Authorization' => "Basic $authHeader",
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->asForm()->post($tokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'redirect_uri' => $redirectUri
        ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];

            // Save Access Token
            Storage::put('ebay_access_token.txt', $accessToken);

            $this->info("✅ eBay Access Token Generated Successfully!");
            $this->info("🔐 Access Token: $accessToken");
            $this->info("📂 Token stored in: storage/app/ebay_access_token.txt");
        } else {
            Log::error('❌ Failed to get Access Token', ['response' => $response->body()]);
            $this->error("❌ Failed to get Access Token");
        }
    }
}
