<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Http\Middleware\EbayAuthMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class EbaySyncService
{
    //private $baseUrl = 'https://api.bigcommerce.com/stores/u4thb/v3';
    private $baseUrl = 'https://api.bigcommerce.com/stores/l8m8i2ai7x/v3';

    private $tokenFile = '';

    private $bigCommerceHeaders = [
        //'X-Auth-Token' => '8solk9a5bhgkv19z7529lelq3c6mw24',
        'X-Auth-Token' => '6tlsgp8ihjtquecp9lyyhqd58hpadez',
        'Content-Type' => 'application/json',
    ];

    private $accessToken, $ebayEnvType;

    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $scopes = 'https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.inventory';

    private $ebayUsername;
    private $ebayPassword;

    public function __construct(Request $request)
    {
        Log::channel('stderr')->info('now in EbaySyncService  consutructur method.');
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
        Log::channel('stderr')->info('now in EbaySyncService now in constructure');
        $ebayAccessToken = $request->accessToken;
        Log::channel('stderr')->info( '$ebayAccessToken ::'.$ebayAccessToken);
        Log::channel('stderr')->info('now in EbaySyncService now calling getUpdateAccessToken()');
        $ebayAccessToken = $this->getUpdateAccessTokenService($ebayAccessToken);
        Log::channel('stderr')->info( '$ebayAccessToken ::'.$ebayAccessToken);
        $this->accessToken = $ebayAccessToken;
        Log::channel('stderr')->info('now in EbaySyncService now at end of  EbaySyncService consutructur method :: $this->accessToken ::'.$this->accessToken);
    }

    public function syncProductToEbay($sku)
    {
        Log::channel('stderr')->info('now in EbaySyncService  syncProductToEbay() with '.$sku);
        Log::channel('stderr')->info('now in EbaySyncService  going to call createEbayProductWithBCSkuService()');
        // Your eBay sync logic here
        $this->createEbayProductWithBCSkuService($sku);

        Log::info("Syncing product to eBay: " . $sku);
        return "Synced SKU: " . $sku;
    }

    public function getBigCommerceProductDetailsBySKUService($sku)
    {
        Log::channel('stderr')->info('now in EbaySyncService  == getBigCommerceProductDetailsBySKUService()');
        $url = $this->baseUrl . '/catalog/products?sku=' . $sku;
        
        $response = Http::withHeaders($this->bigCommerceHeaders)->get($url);
        if ($response->successful()) {
            //return response()->json($response->json()['data']);
            return $response->json()['data']['0'];
        } else {
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $response->body(),
            ], $response->status());
        }
    }

    public function createEbayProductWithBCSkuService($bcsku)
    {
        Log::channel('stderr')->info('now in EbaySyncService  == createEbayProductWithBCSkuService()');
        Log::channel('stderr')->info('now in EbaySyncService  going to call getBigCommerceProductDetailsBySKUService()');
        // Get the product list
        $product = $this->getBigCommerceProductDetailsBySKUService($bcsku);
        Log::channel('stderr')->info('now in EbaySyncService  get big commerce produc details with '.$bcsku.' to call getBigCommerceProductDetailsBySKUService() ::'.json_encode($product));
        Log::channel('stderr')->info('now in EbaySyncService die');
        if (empty($product)) {
            return response()->json(['error' => 'Please provide valid Big Commerce SKU.'], 404);
        }

        // eBay API Endpoint (Sandbox)
        $ebayApiUrl = "https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/inventory_item/";
        //echo '$ebayApiUrl ::'.$ebayApiUrl;die;

        // Loop through each product and send to eBay
        $responses = [];
        $sku = $product['sku'];
        $quantity = $product['inventory_level'];
        $quantity = ($quantity > 2) ? $quantity : 2;
        $brandId = $product['brand_id'];
        $brandName = $this->getBigCommerceBrandNameService($brandId);
        $mpn = ($product['mpn'] == '') ? $sku : $product['mpn'];
        $imageArr = $this->getBigCommerceProductImagesService($product['id']);

        $weight = $product['weight'];
        $weight = ($weight == null) ? "5.000" : $weight;
        //$weight = (float) $weight;

        $width = $product['width'];
        $width = ($width == null) ? "6.0" : $width;
        //$width = (float) $width;

        $height = $product['height'];
        $height = ($height == null) ? "2.0" : $height;
        //$height = (float) $height;

        $length = $product['depth'];
        $length = ($length == null) ? "6.0" : $length;
        //$length = (float) $length;

        $productData = [
            "availability" => [
                "shipToLocationAvailability" => [
                    "quantity" => $quantity
                ]
            ],
            "condition" => "NEW",
            "sku" => $sku,
            "product" => [
                "title" => str_pad(substr($product['name'], 0, 40), 40, ' ', STR_PAD_RIGHT),
                "description" => strip_tags($product['description']),
                "aspects" => [
                    "Brand" => [$brandName]
                ],
                "brand" => $brandName,
                "mpn" => $mpn,
                "imageUrls" => $imageArr
            ],
            "packageWeightAndSize" => [
                "dimensions" => [
                    "width" => $width,
                    "length" => $length,
                    "height" => $height,
                    "unit" => "INCH"
                ],
                "shippingIrregular" => false,
                "packageType" => "PACKAGE_THICK_ENVELOPE", // Ensure valid package type VERY_LARGE_PACK, FREIGHT, LARGE_ENVELOPE, USPS_FLAT_RATE_ENVELOPE, USPS_LARGE_PACK
                "weight" => [
                    "unit" => "POUND",
                    "value" => $weight
                ]
            ]
        ];
        // Debugging JSON payload before sending
        $productJson = json_encode($productData, JSON_PRETTY_PRINT);
        Log::channel('stderr')->info("inventory craete or update Request Payload: " . $productJson);

        $response = Http::withHeaders([
            'Authorization' => "Bearer $this->accessToken",
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US'
        ])->put($ebayApiUrl . $sku, $productData); // Convert to raw JSON   json_decode(json_encode($productData), true)

        //echo '<pre>';print_r($response->json());die;
        if ($response->successful()) {
            Log::channel('stderr')->info("Success Request Payload: ", [$response->json()]);
            $responses[] = [
                'sku' => $sku,
                'status' => $response->status(),
                'response' => $response->json()
            ];
            Log::channel('stderr')->info("going to call createOrRePlaceOffer() with ::$sku");
            $this->createOrRePlaceOfferService($sku);
        } else {
            Log::channel('stderr')->info($ebayApiUrl . $sku . ' == failed');
            Log::channel('stderr')->info("create inventory fail response info: ", [$response->json()]);
        }
        return response()->json([
            //'message' => 'eBay inventory items created successfully',
            'responses' => $responses
        ]);
    }


    public function getCategoryIdFromEbayService($categoryName)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/commerce/taxonomy/v1/category_tree/0/get_category_suggestions?q=" . $categoryName);

        //return $response->successful() ? $response->json() : null;
        if ($response->successful()) {
            return $response->json()->categorySuggestions[0]->category->categoryId;
        } else {
            return response()->json([
                'error' => 'Failed to fetch category',
                'message' => $response->body(),
            ], $response->status());
        }
        //return $response->successful() ? $response->json()->categorySuggestions[0]->category->categoryId : null;
    }

    public function createOrRePlaceOfferService($sku)
    {
        Log::channel('stderr')->info("checking offer details to related to $sku");
        //now to check sku has offer Or Not
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/offer?sku={$sku}");

        Log::channel('stderr')->info("response for checking $sku for offer details ::", [$response->json()]);

        $offerIdNumber = false;
        $offerId = '';
        if ($response->successful()) {
            Log::channel('stderr')->info('now in EbaySyncService find offer and checking is published ot not');
            $offerInfo = $response->json()['offers']['0'];
            if ($offerInfo['status'] == 'UNPUBLISHED') {
                Log::info("going to publish the offer for $sku with " . $offerInfo['offerId']);
                $this->publishEbayOfferService($offerInfo['offerId'],$sku);
            } else {
                Log::channel('stderr')->info("offer for $sku with " . $offerInfo['offerId'] . " had already published");
            }
        } else {
            Log::channel('stderr')->info("Going to create new offer for $sku");
            $offerIdResponse = $this->createOfferService($sku);
            Log::channel('stderr')->info("offer created response ::", [$offerIdResponse]);
            if ($offerIdResponse) {
                Log::channel('stderr')->info("Now going to publish the offer :: " . $offerIdResponse['offerId']);
                $this->publishEbayOfferService($offerIdResponse['offerId'],$sku);
            }
        }
    }

    public function getBigCommerceCategoryNameService($categoryId)
    {
        $url = $this->baseUrl . '/catalog/categories/' . $categoryId;

        $response = Http::withHeaders($this->bigCommerceHeaders)->get($url);

        if ($response->successful()) {
            //return response()->json($response->json()['data']);
            return $response->json()['data']['name'];
        } else {
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $response->body(),
            ], $response->status());
        }
    }

    public function createOfferService($sku)
    {
        Log::channel('stderr')->info('now in EbaySyncService now in createOffer()');
        $validatedData = [];
        $validatedData['sku'] = $sku;
        Log::channel('stderr')->info('now in EbaySyncService now calling getBigCommerceProductDetailsBySKU() with $sku::'.$sku);
        $productData = $this->getBigCommerceProductDetailsBySKUService($sku);
        $price = $productData['price'];
        $validatedData['price'] = $price;
        $quantity = $productData['inventory_level'];
        $validatedData['quantity'] = ($quantity > 2) ? $quantity : 2;
        $categoryName = $this->getBigCommerceCategoryNameService($productData['categories'][0]);

        // 1. Fetch Inventory Item Data
        $inventoryData = $this->getInventoryItemOneService($validatedData['sku']);
        Log::channel('stderr')->info('now in EbaySyncService $inventoryData ::',[$inventoryData]);
        if (!$inventoryData) {
            return response()->json(['error' => 'Inventory item not found'], 404);
        }

        /**
         * **** calling here to get category list data
         */
        //$categoryData = $this->getCategoryList(0);

        // 2. Fetch Required eBay Data
        $marketplaceId = 'EBAY_US'; // Set marketplace ID manually for now  'EBAY_US'; 
        $fulfillmentPolicyId = $this->getFulfillmentPolicyService($marketplaceId);
        Log::channel('stderr')->info('now in EbaySyncService $fulfillmentPolicyId ::'.$fulfillmentPolicyId);
        $paymentPolicyId = ($this->ebayEnvType == '.sandbox.') ? $this->getPaymentPolicyService($marketplaceId) : "264239928014";
        Log::channel('stderr')->info('now in EbaySyncService $paymentPolicyId ::'.$paymentPolicyId);
        $returnPolicyId = $this->getReturnPolicyService($marketplaceId);
        Log::channel('stderr')->info('now in EbaySyncService $returnPolicyId ::'.$returnPolicyId);
        $categoryId = "182189"; // Replace with actual category retrieval logic
        $currency = "USD";
        //$categoryId =  $categoryData->
        $merchantLocationKey = $this->getMerchantLocationService();
        Log::channel('stderr')->info('now in EbaySyncService $merchantLocationKey ::'.$merchantLocationKey);
        //$merchantLocationKey = 'default-location';
        //echo '$fulfillmentPolicyId :: '.$fulfillmentPolicyId.' == $paymentPolicyId ::'.$paymentPolicyId.' == $returnPolicyId ::'.$returnPolicyId.' == $merchantLocationKey ::'.$merchantLocationKey;die;


        // Ensure required IDs exist
        /*if (!$fulfillmentPolicyId || !$paymentPolicyId || !$returnPolicyId || !$merchantLocationKey) {
            return response()->json(['error' => 'Failed to fetch required eBay data'], 400);
        }*/


        $quantity = $inventoryData['availability']['shipToLocationAvailability']['quantity'] ?? 10;
        $lotSize = max(2, $inventoryData['availability']['shipToLocationAvailability']['quantity'] ?? 2);
        $quantityLimitPerBuyer = 2;
        $countryCode = "US";
        $shippingPackageCode = "UPSNextDay";
        $shippingServiceType = "DOMESTIC";
        // 3. Prepare Offer Data
        
        $offerData = [
            "availableQuantity" => $quantity,
            "categoryId" => $categoryId,
            "format" => "FIXED_PRICE",
            "hideBuyerDetails" => false,
            "includeCatalogProductDetails" => false,
            "listingDescription" => $inventoryData['product']['description'],
            "listingPolicies" => [
                "eBayPlusIfEligible" => false,
                "fulfillmentPolicyId" => $fulfillmentPolicyId,
                "paymentPolicyId" => $paymentPolicyId, //"264239928014",
                "returnPolicyId" => $returnPolicyId,
                "shippingCostOverrides" => [
                    [
                        "shippingServiceType" => $shippingServiceType
                    ]
                ]
            ],
            "lotSize" => $lotSize,
            "marketplaceId" => $marketplaceId,
            "pricingSummary" => [
                "price" => [
                    "currency" => $currency,
                    "value" => $validatedData['price']
                ],
                "pricingVisibility" => "PRE_CHECKOUT"
            ],
            "location" => [
                "countryCode" => $countryCode,
            ],
            "quantityLimitPerBuyer" => $quantityLimitPerBuyer,
            "sku" => $validatedData['sku'],
            "merchantLocationKey" => $merchantLocationKey,
            "shippingOptions" => [
                [
                    "shippingService" => "FedExFreight",
                    "priority" => 0,
                    "shippingCost" => [
                        "value" => "10.00",
                        "currency" => "USD"
                    ],
                    "additionalShippingCost" => [
                        "value" => "2.00",
                        "currency" => "USD"
                    ],
                    "freeShipping" => false,
                    "shippingCarrierCode" => "FedEx",
                    "shippingCostType" => "FLAT_RATE"
                ]
            ]
        ];

        //echo json_encode($offerData);die;
        Log::channel('stderr')->info("data to craete offer ::", [$offerData]);

        // 4. Make Offer API Call
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US'
        ])->post('https://api' . $this->ebayEnvType . 'ebay.com/sell/inventory/v1/offer', $offerData);
        Log::channel('stderr')->info("offer created successfully ::", [$response->json()]);
        Log::channel('stderr')->info("offer created successfully with offer id::" . $response->json()['offerId'], [$response->json()]);
        return ($response->successful()) ? $response->json() : false;
    }


    /**
     * Get Fulfillment Policy ID
     */
    public function getFulfillmentPolicyService($marketplaceId)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/account/v1/fulfillment_policy?marketplace_id={$marketplaceId}");

        return $response->successful() ? $response->json()['fulfillmentPolicies'][0]['fulfillmentPolicyId'] ?? null : null;
    }


    /**
     * Get Payment Policy ID
     */
    public function getPaymentPolicyService($marketplaceId)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/account/v1/payment_policy?marketplace_id={$marketplaceId}");

        return $response->successful() ? $response->json()['paymentPolicies'][0]['paymentPolicyId'] ?? null : null;
    }

    /**
     * Get Return Policy ID
     */
    public function getReturnPolicyService($marketplaceId)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/account/v1/return_policy?marketplace_id={$marketplaceId}");

        return $response->successful() ? $response->json()['returnPolicies'][0]['returnPolicyId'] ?? null : null;
    }

    /**
     * Get Merchant Location Key
     */
    public function getMerchantLocationService()
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/location");

        //echo '<pre>';print_r($response->json());

        return $response->successful() ? $response->json()['locations'][0]['merchantLocationKey'] ?? null : null;
    }

    /**
     * Get Inventory Item from eBay
     */
    public function getInventoryItemOneService($sku)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/inventory_item/{$sku}");

        if($response->successful()){ 
            Log::channel('stderr')->info('now in EbaySyncService get details of inventoryr item with $sku ::'.$sku,[$response->json()]);
            return $response->json();
         }else{
            Log::channel('stderr')->info('now in EbaySyncService fail to get details of inventoryr item with $sku ::'.$sku);
            return null;
         } 
    }

    public function publishEbayOfferService($offerId,$sku)
    {
        /*$validatedData = $request->validate([
            'offerId' => 'required|string'
        ]);
        $validatedData['offerId']
        */

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US'
        ])->post('https://api' . $this->ebayEnvType . 'ebay.com/sell/inventory/v1/offer/' . $offerId . '/publish');

        Log::info('response ::', [$response->json()]);
        if($response->successful()){
            $this->removeSkuFromJSONFileService($sku);
            return response()->json(['type'=>'sucess','data'=>$response->json(), 'status'=>$response->status()]);
        }else{
            return response()->json(['type'=>'fail'], 400);
        }
        
    }

    public function removeSkuFromJSONFileService($sku)
    {
        Log::info('now at removeSkuFromJSONFile()');
        $filePath = 'big-commerce-sku.json';

        if (!Storage::exists($filePath)) {
            Log::info('file not exists $filePath ::'.$filePath);
            return response()->json(['message' => 'File not found.'], 404);
        }

        $jsonData = json_decode(Storage::get($filePath), true);
        if (!is_array($jsonData)) {
            return response()->json(['message' => 'Invalid file content.'], 400);
        }

        $jsonData = array_filter($jsonData, function ($item) use ($sku) {
            return $item !== $sku;
        });

        Storage::put($filePath, json_encode(array_values($jsonData), JSON_PRETTY_PRINT));

        return response()->json(['message' => 'SKU removed successfully', 'data' => $jsonData]);
    }

    public function showSkuFromJSONFileService()
    {
        $filePath = 'big-commerce-sku.json';

        if (!Storage::exists($filePath)) {
            return view('sku_list', ['skus' => []]);
        }

        $jsonData = json_decode(Storage::get($filePath), true);
        if (!is_array($jsonData)) {
            $jsonData = [];
        }

        return view('sku_list', ['skus' => $jsonData]);
    }

    public function removeSkuFromJSONFileWebService($sku)
    {
        Log::info('now at removeSkuFromJSONFile()');
        $filePath = 'big-commerce-sku.json';

        if (!Storage::exists($filePath)) {
            Log::info('file not exists $filePath ::'.$filePath);
            //return response()->json(['message' => 'File not found.'], 404);
            return Redirect::back()->withErrors(['msg' => 'File not found.']);
        }

        $jsonData = json_decode(Storage::get($filePath), true);
        if (!is_array($jsonData)) {
            //return response()->json(['message' => 'Invalid file content.'], 400);
            return Redirect::back()->withErrors(['msg' => 'Invalid file content.']);

        }

        $jsonData = array_filter($jsonData, function ($item) use ($sku) {
            return $item !== $sku;
        });

        Storage::put($filePath, json_encode(array_values($jsonData), JSON_PRETTY_PRINT));

        //return response()->json(['message' => 'SKU removed successfully', 'data' => $jsonData]);
        return redirect('/bigcommerce/show-bc-sku')->with(['msg' => 'SKU removed successfully.']);
    }

    public function getBigCommerceBrandNameService($brandId)
    {
        $url = $this->baseUrl . '/catalog/brands/' . $brandId;

        $response = Http::withHeaders($this->bigCommerceHeaders)->get($url);

        if ($response->successful()) {
            //return response()->json($response->json()['data']);
            return $response->json()['data']['name'];
        } else {
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $response->body(),
            ], $response->status());
        }
    }

    public function getUpdateAccessTokenService($ebayAccessToken=''){
        Log::channel('stderr')->info('now in EbaySyncService now in getUpdateAccessToken');
        if ($ebayAccessToken == '') {
            Log::channel('stderr')->info( 'now going to calling readStoredToken()');
            // Check if we already have a valid token
            $storedToken = $this->readStoredTokenService();
            Log::channel('stderr')->info( '$storedToken ::'.json_encode($storedToken));
            if ($storedToken && !$this->isTokenExpiredService($storedToken)) {
                $ebayAccessToken = $storedToken['access_token'];
                Log::channel('stderr')->info('now in EbaySyncService  token  not expired $ebayAccessToken ::'.$ebayAccessToken);
            }else if ($storedToken && isset($storedToken['refresh_token'])) {
                Log::channel('stderr')->info('now in EbaySyncService token expired. so going to call refresh token');
                Log::channel('stderr')->info( 'going to call refreshUserToken()');
                // Try to refresh token if exists
                $newToken = $this->isTokenExpiredService($storedToken['refresh_token']);
                Log::channel('stderr')->info( '$newToken ::'.json_encode($newToken));
                if ($newToken) {
                    Log::channel('stderr')->info('now in EbaySyncService  calling storeToken()');
                    $this->storeTokenService($newToken);
                    $ebayAccessToken = $newToken['access_token'];
                    Log::channel('stderr')->info('now in EbaySyncService  token  not expired $ebayAccessToken ::'.$ebayAccessToken);
                }
            }
        }
        
        //echo '$ebayAccessToken ::'.$ebayAccessToken;
        if (!$ebayAccessToken) {
            Log::channel('stderr')->info( 'now in constructure $ebayAccessToken::'.$ebayAccessToken);
        }
        return $ebayAccessToken;
    }

    public function getBigCommerceProductImagesService($productId)
    {
        $url = $this->baseUrl . '/catalog/products/' . $productId . '/images';

        $response = Http::withHeaders($this->bigCommerceHeaders)->get($url);

        if ($response->successful()) {
            //return response()->json($response->json()['data']);
            $data = $response->json()['data'];
            $imageArr = [];
            foreach ($data as $k => $v) {
                $imageArr[] = $v['url_standard'];
            }
            return $imageArr;
        } else {
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $response->body(),
            ], $response->status());
        }
    }

    /**
     * Read stored token from text file.
     */
    public function readStoredTokenService()
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
    public function isTokenExpiredService($tokenData)
    {
        return !isset($tokenData['expires_at']) || time() >= $tokenData['expires_at'];
    }

    /**
     * Store the access token in a text file.
     */
    public function storeTokenService($tokenData)
    {
        Storage::put($this->tokenFile, json_encode($tokenData));
    }

    /**
     * Refresh the user access token.
     */
    public function refreshUserTokenService($refreshToken)
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
