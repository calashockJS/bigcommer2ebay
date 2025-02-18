<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;


class ApiController extends Controller
{
    //private $baseUrl = 'https://api.bigcommerce.com/stores/u4thb/v3';
    private $baseUrl = 'https://api.bigcommerce.com/stores/l8m8i2ai7x/v3';

    private $bigCommerceHeaders = [
        //'X-Auth-Token' => '8solk9a5bhgkv19z7529lelq3c6mw24',
        'X-Auth-Token' => '6tlsgp8ihjtquecp9lyyhqd58hpadez',
        'Content-Type' => 'application/json',
    ];

    private $accessToken, $ebayEnvType;

    public function __construct(Request $request)
    {
        // Get environment type value from .env
        $envTypeEbay = env('EBAY_ENV_TYPE');
        $this->ebayEnvType = $envTypeEbay;

        $ebayAccessToken = $request->accessToken;
        if ($ebayAccessToken == '') {
            if ($envTypeEbay == '.sandbox.') {
                // Get eBay Access Token from .env
                $ebayAccessToken = env('EBAY_ACCESS_TOKEN_SANDBOX');
            } else {
                $ebayAccessToken = env('EBAY_ACCESS_TOKEN');
            }
        }

        if (!$ebayAccessToken) {
            return response()->json(['error' => 'eBay Access Token not found in .env'], 401);
        }
        $this->accessToken = $ebayAccessToken;
    }

    /**
     * Fetch products from BigCommerce API
     *
     * @OA\Get(
     *     path="/api/bigcommerce/getProductsAndSaveJson",
     *     summary="Retrieve products and save json",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of products to retrieve",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Pagination offset",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="price", type="number"),
     *                 @OA\Property(property="availability", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid request"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    /**
     * Fetch products from BigCommerce API
     *
    public function getProductsAndSaveJson(Request $request){
        // Get limit and offset from request, set default values if not provided
        $limit = $request->query('limit', 10); // Default limit: 10
        $offset = $request->query('offset', 0); // Default offset: 0

        // Construct API URL with pagination parameters
         $url = $this->baseUrl . "/catalog/products?limit={$limit}&page={$offset}";
        //$url = $this->baseUrl . '/catalog/products';

        $response = Http::withHeaders($this->bigCommerceHeaders)->get($url);
        
        if ($response->successful()) {
            // Format date as dd-mm-yyyy
            $date = now()->format('d-m-Y');
    
            // Create folder path in storage
            $folderPath = storage_path("app/public/{$date}");
    
            // Ensure directory exists
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }
    
            // Define the JSON filename based on limit and offset
            $fileName = "{$date}-limit-{$limit}-offset-{$offset}.json";
            $filePath = "{$folderPath}/{$fileName}";
    
            // Check if the file already exists and delete it
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Save the response as a JSON file
            file_put_contents($filePath, json_encode($response->json()['data'], JSON_PRETTY_PRINT));
    
            return response()->json([
                'message' => 'File saved successfully',
                'file_path' => $filePath
            ]);
        } else {
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $response->body(),
            ], $response->status());
        }
    }*/

    /**
     * Fetch products from BigCommerce API
     *
     * @OA\Get(
     *     path="/api/bigcommerce/products",
     *     summary="Retrieve products",
     *     tags={"Products Migrate"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of products to retrieve",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Pagination offset",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="price", type="number"),
     *                 @OA\Property(property="availability", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid request"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getProducts()
    {
        $url = $this->baseUrl . '/catalog/products';

        $response = Http::withHeaders($this->bigCommerceHeaders)->get($url);

        if ($response->successful()) {
            //return response()->json($response->json()['data']);
            return $response->json()['data'];
            /*$data = $response->json()['data'];
            echo '<pre>';print_r($data);die;
            foreach($data as $k => $v){
                $categoryName = $this->getBigCommerceCategoryName($v['categories'][0]);
                $this->getCategoryIdFromEbay($categoryName);
                die;
            }*/
        } else {
            return response()->json([
                'error' => 'Failed to fetch products',
                'message' => $response->body(),
            ], $response->status());
        }
    }


    /**
     * Create eBay Product
     *
     * @OA\Post(
     *     path="/api/ebay/bc-sku-to-ebay-listing",
     *     summary="Create a inventory item, then create a offer and publishh that on eBay",
     *     tags={"BC to eBay Listing"},
     *     @OA\Parameter(
     *         name="WEBHOOK-SECURITY-KEY",
     *         in="header",
     *         required=true,
     *         description="Security key for authentication",
     *         @OA\Schema(type="string", default="dasc23c3-ca45v8v9-90asds")
     *     ),
     *     @OA\Parameter(
     *         name="bcsku",
     *         in="header",
     *         required=true,
     *         description="Big Commerce SKU for Ebay Listing",
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product successfully created on eBay",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="responses", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="sku", type="string"),
     *                 @OA\Property(property="status", type="integer"),
     *                 @OA\Property(property="response", type="object")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized - eBay Access Token not found"),
     *     @OA\Response(response=404, description="No products found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function createEbayProductWithBCSku(Request $request)
    {
        //$bcsku,
        // Validate WEBHOOK-SECURITY-KEY header
        if (!$request->hasHeader('WEBHOOK-SECURITY-KEY')) {
            return response()->json([
                'message' => 'WEBHOOK-SECURITY-KEY header is required',
                'error' => 'Missing required header'
            ], 401);
        }

        $securityKey = $request->header('WEBHOOK-SECURITY-KEY');
        
        // Check if security key is blank
        if (empty($securityKey)) {
            return response()->json([
                'message' => 'WEBHOOK-SECURITY-KEY cannot be empty',
                'error' => 'Invalid header value'
            ], 401);
        }
        $securityKeyValue = env('BC_WEBHOOK_SECURITY_KEY');
        // Validate against expected security key value
        if ($securityKey !== $securityKeyValue) {
            return response()->json([
                'message' => 'Invalid WEBHOOK-SECURITY-KEY provided',
                'error' => 'Unauthorized'
            ], 401);
        }

        // Validate WEBHOOK-SECURITY-KEY header
        if (!$request->hasHeader('bcsku')) {
            return response()->json([
                'message' => 'BC Sku header is required',
                'error' => 'Missing required header'
            ], 401);
        }

        $bcsku = $request->header('bcsku');
        
        // Check if security key is blank
        if (empty($bcsku)) {
            return response()->json([
                'message' => 'BC Sku cannot be empty',
                'error' => 'Invalid header value'
            ], 401);
        }

        //die("RRr");
        // Get the product list
        $product = $this->getBigCommerceProductDetailsBySKU($bcsku);
        //echo '<pre>';print_r($products);die;

        if (empty($products)) {
            return response()->json(['error' => 'Please provide valid Big Commerce SKU.'], 404);
        }

        // eBay API Endpoint (Sandbox)
        $ebayApiUrl = "https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/inventory_item/";
        //echo '$ebayApiUrl ::'.$ebayApiUrl;die;

        // Loop through each product and send to eBay
        $responses = [];
        $sku = $product['sku'];
        $quantity = $product['inventory_level'];
        $quantity = ($quantity>2)?$quantity:2; 
        $brandId = $product['brand_id'];
        $brandName = $this->getBigCommerceBrandName($brandId);
        $mpn = ($product['mpn']=='') ? $sku : $product['mpn'];
        $imageArr = $this->getBigCommerceProductImages($product['id']);

        $weight = $product['weight'];
        $weight = ($weight==null) ? "5.000" : $weight;
        //$weight = (float) $weight;

        $width = $product['width'];
        $width = ($width==null) ? "6.0" : $width;
        //$width = (float) $width;

        $height = $product['height'];
        $height = ($height==null) ? "2.0" : $height;
        //$height = (float) $height;

        $length = $product['depth'];
        $length = ($length==null) ? "6.0" : $length;
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
        Log::info("inventory craete or update Request Payload: ".$productJson);

        $response = Http::withHeaders([
            'Authorization' => "Bearer $this->accessToken",
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US'
        ])->put($ebayApiUrl . $sku, $productData); // Convert to raw JSON   json_decode(json_encode($productData), true)

        //echo '<pre>';print_r($response->json());die;
        if ($response->successful()) {
            Log::info("Success Request Payload: ", [$response->json()]);
            $responses[] = [
                'sku' => $sku,
                'status' => $response->status(),
                'response' => $response->json()
            ];
            Log::info("going to call createOrRePlaceOffer() with ::$sku");
            $this->createOrRePlaceOffer($sku);
        }else{
            Log::info($ebayApiUrl . $sku.' == failed');
            Log::info("create inventory fail response info: ", [$response->json()]);
        }
        return response()->json([
            //'message' => 'eBay inventory items created successfully',
            'responses' => $responses
        ]);
    }

    /**
     * Create eBay Product
     *
     * @OA\Get(
     *     path="/api/ebay/create-inventory",
     *     summary="Create a product on eBay",
     *     tags={"eBay Inventory"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product successfully created on eBay",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="responses", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="sku", type="string"),
     *                 @OA\Property(property="status", type="integer"),
     *                 @OA\Property(property="response", type="object")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized - eBay Access Token not found"),
     *     @OA\Response(response=404, description="No products found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function createEbayProduct()
    {
        //die("RRr");
        // Get the product list
        $products = $this->getProducts();
        //echo '<pre>';print_r($products);die;

        if (empty($products)) {
            return response()->json(['error' => 'No products found'], 404);
        }

        // eBay API Endpoint (Sandbox)
        $ebayApiUrl = "https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/inventory_item/";
        //echo '$ebayApiUrl ::'.$ebayApiUrl;die;

        // Loop through each product and send to eBay
        $responses = [];
        $count = 0;
        foreach ($products as $product) {
            if ($count < 3) {
                Log::info('now $count is ::'.$count.' is going to continue statement');
                $count++;
                continue;
            }

            if($count > 4){
                Log::info('now $count is ::'.$count.' is going to break the loop');
                break;
            }
            Log::info('now $count is ::'.$count.' going to create or udpate inventory item with $sku ::'.$product['sku']);
            $sku = $product['sku'];
            $quantity = $product['inventory_level'];
            $quantity = ($quantity>2)?$quantity:2; 
            $brandId = $product['brand_id'];
            $brandName = $this->getBigCommerceBrandName($brandId);
            $mpn = ($product['mpn']=='') ? $sku : $product['mpn'];
            $imageArr = $this->getBigCommerceProductImages($product['id']);

            $weight = $product['weight'];
            $weight = ($weight==null) ? "5.000" : $weight;
            //$weight = (float) $weight;

            $width = $product['width'];
            $width = ($width==null) ? "6.0" : $width;
            //$width = (float) $width;

            $height = $product['height'];
            $height = ($height==null) ? "2.0" : $height;
            //$height = (float) $height;

            $length = $product['depth'];
            $length = ($length==null) ? "6.0" : $length;
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
            Log::info("inventory craete or update Request Payload: ".$productJson);

            $response = Http::withHeaders([
                'Authorization' => "Bearer $this->accessToken",
                'Content-Type' => 'application/json',
                'Content-Language' => 'en-US'
            ])->put($ebayApiUrl . $sku, $productData); // Convert to raw JSON   json_decode(json_encode($productData), true)

            //echo '<pre>';print_r($response->json());die;
            if ($response->successful()) {
                Log::info("Success Request Payload: ", [$response->json()]);
                $responses[] = [
                    'sku' => $sku,
                    'status' => $response->status(),
                    'response' => $response->json()
                ];
                Log::info("going to call createOrRePlaceOffer() with ::$sku");
                $this->createOrRePlaceOffer($sku);
            }else{
                Log::info($ebayApiUrl . $sku.' == failed');
                Log::info("create inventory fail response info: ", [$response->json()]);
            }

            //return response()->json($responses);

            $count++;
        }

        return response()->json([
            //'message' => 'eBay inventory items created successfully',
            'responses' => $responses
        ]);
    }

    private function createOrRePlaceOffer($sku){
        Log::info("checking offer details to related to $sku");
        //now to check sku has offer Or Not
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/offer?sku={$sku}");

        Log::info("response for checking $sku for offer details ::",[$response->json()]);

        $offerIdNumber = false;
        $offerId ='';
        if($response->successful()){
            Log::info('find offer and checking is published ot not');
            $offerInfo = $response->json()['offers']['0'];
            if($offerInfo['status'] == 'UNPUBLISHED'){
                Log::info("going to publish the offer for $sku with ".$offerInfo['offerId']);
                $this->publishEbayOffer($offerInfo['offerId']);   
            }else{
                Log::info("offer for $sku with ".$offerInfo['offerId']." had already published");
            }
        }else{
            Log::info("Going to create new offer for $sku");
            $offerIdResponse = $this->createOffer($sku);
            Log::info("offer created response ::",[$offerIdResponse]);
            if($offerIdResponse){
                Log::info("Now going to publish the offer :: ".$offerIdResponse['offerId']);
                $this->publishEbayOffer($offerIdResponse['offerId']);
            }
        }
    }

    /**
     * Generate eBay Access Token
     *
     * @OA\Post(
     *     path="/api/ebay/token",
     *     summary="Generate eBay Access Token",
     *     tags={"eBay"},
     *     @OA\Response(
     *         response=200,
     *         description="Access token generated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="token_type", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid request"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function generateEbayAccessToken()
    {
        // Run the Artisan command to generate the eBay access token
        Artisan::call('ebay:generate-access-token');

        // Capture the output from the command
        $output = Artisan::output();

        // Log the output (optional)
        Log::info("eBay Access Token Command Output: " . $output);

        // Check if the access token was stored
        if (Storage::exists('ebay_access_token.txt')) {
            $accessToken = Storage::get('ebay_access_token.txt');

            return response()->json([
                'message' => 'eBay Access Token Generated Successfully',
                'access_token' => $accessToken
            ]);
        } else {
            return response()->json([
                'error' => 'Failed to generate eBay Access Token',
                'output' => $output
            ], 500);
        }
    }

    /**
     * Fetch all Ebay Inventory Items
     *
     * @OA\Get(
     *     path="/api/ebay/get-inventory-items",
     *     summary="Retrieve all inventory item",
     *     tags={"eBay Inventory"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of inventory item to retrieve",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Pagination offset",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="price", type="number"),
     *                 @OA\Property(property="availability", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid request"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getAllEbayInventoryItems(Request $request)
    {
        // eBay API Endpoint (Sandbox)
        $ebayApiUrl = "https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/inventory_item?limit=20&offset=0";

        // Send request to eBay Sandbox API
        $response = Http::withHeaders([
            'Authorization' => "Bearer $this->accessToken",
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US'
        ])->get($ebayApiUrl);

        // Log Response
        Log::info("eBay API Response", [$response->json()]);

        $responses[] = [
            'status' => $response->status(),
            'response' => $response->json()
        ];

        return response()->json([
            'message' => 'Get all eBay inventory items successfully',
            'responses' => $responses
        ]);
    }


    /**
     * Retrieve an inventory item from eBay
     *
     * @OA\Get(
     *     path="/api/ebay/inventory-item/{sku}",
     *     summary="Get Inventory Item",
     *     description="Fetches an inventory item from eBay by SKU",
     *     tags={"eBay Inventory"},
     *     @OA\Parameter(
     *         name="sku",
     *         in="path",
     *         required=true,
     *         description="SKU of the inventory item",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="sku", type="string", example="WHEELS1"),
     *             @OA\Property(property="locale", type="string", example="en_US"),
     *             @OA\Property(
     *                 property="product",
     *                 type="object",
     *                 @OA\Property(property="title", type="string", example="Car Wheels"),
     *                 @OA\Property(property="description", type="string", example="4 Big wheels for a kart."),
     *                 @OA\Property(property="brand", type="string", example="Luigi"),
     *                 @OA\Property(
     *                     property="imageUrls",
     *                     type="array",
     *                     @OA\Items(type="string", example="https://cdn11.bigcommerce.com/s-u4thb/images/stencil/80w/products/757/30/Mario_mario_kart_8_deluxe__08113.1738858327.png")
     *                 )
     *             ),
     *             @OA\Property(property="condition", type="string", example="NEW"),
     *             @OA\Property(
     *                 property="availability",
     *                 type="object",
     *                 @OA\Property(
     *                     property="shipToLocationAvailability",
     *                     type="object",
     *                     @OA\Property(property="quantity", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Inventory item not found")
     *         )
     *     )
     * )
     */

    public function getInventoryItem($sku)
    {
        // Get environment type value from .env
        $ebayEnvType = env('EBAY_ENV_TYPE');

        // eBay API credentials
        //$accessToken = 'YOUR_EBAY_ACCESS_TOKEN'; // Replace with your eBay OAuth token
        $endpoint = "https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/inventory_item/{$sku}"; // eBay API URL
        //$endpoint = "https://api.sandbox.ebay.com/sell/inventory/v1/inventory_item/WHEELS1";

        // Make API request
        $response = Http::withHeaders([
            'Authorization' => "Bearer $this->accessToken",
            'Content-Type' => 'application/json',
        ])->get($endpoint);

        $responses[] = [
            'status' => $response->status(),
            'response' => $response->json()
        ];

        return response()->json([
            'message' => 'Get inventory items successfully',
            'responses' => $responses
        ]);
    }


    /**
     * Create an Offer on eBay using SKU
     *
     * @OA\Post(
     *     path="/api/ebay/create-offer/{sku}",
     *     summary="Create an eBay Offer",
     *     description="Dynamically fetches required data from eBay and creates an offer",
     *     tags={"eBay Offers"},
     *     @OA\Parameter(
     *         name="sku",
     *         in="path",
     *         required=true,
     *         description="SKU",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Offer created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="offerId", type="string", example="1234567890"),
     *             @OA\Property(property="status", type="string", example="SUCCESS")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to create offer")
     *         )
     *     )
     * )
     */
    public function createOffer($sku)
    {
        $validatedData =[];
        $validatedData['sku'] = $sku;
        $productData = $this->getBigCommerceProductDetailsBySKU($sku);
        $price = $productData['price'];
        $validatedData['price'] = $price;
        $quantity = $productData['inventory_level'];
        $validatedData['quantity'] = ($quantity>2)?$quantity:2;
        $categoryName = $this->getBigCommerceCategoryName($productData['categories'][0]);

        // 1. Fetch Inventory Item Data
        $inventoryData = $this->getInventoryItemOne($validatedData['sku']);
        if (!$inventoryData) {
            return response()->json(['error' => 'Inventory item not found'], 404);
        }

        /**
         * **** calling here to get category list data
         */
        //$categoryData = $this->getCategoryList(0);

        // 2. Fetch Required eBay Data
        $marketplaceId = 'EBAY_US'; // Set marketplace ID manually for now  'EBAY_US'; 
        $fulfillmentPolicyId = $this->getFulfillmentPolicy($marketplaceId);
        
        $paymentPolicyId = ($this->ebayEnvType == '.sandbox.') ? $this->getPaymentPolicy($marketplaceId): "264239928014";
        $returnPolicyId = $this->getReturnPolicy($marketplaceId);
        $categoryId = "182189"; // Replace with actual category retrieval logic
        $currency = "USD";
        //$categoryId =  $categoryData->
        $merchantLocationKey = $this->getMerchantLocation();
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
        /***
          $offerData = [
            "sku" => $validatedData['sku'],
            "marketplaceId" => $marketplaceId,
            "format" => "FIXED_PRICE",
            "listingDescription" => $inventoryData['product']['description'],
            "availableQuantity" => $inventoryData['availability']['shipToLocationAvailability']['quantity'] ?? 1,
            "pricingSummary" => [
                "price" => [
                    "value" => $validatedData['price'],  // Replace with actual pricing logic
                    "currency" => "USD"
                ]
            ],
            "listingPolicies" =>[
                "fulfillmentPolicies" => [
                    [
                        "shippingCost"=> [
                            "value" => "0.0",
                            "currency" => "USD"
                        ]
                    ]
                ],
                "paymentPolicies" => [
                    [
                        "paymentMethodTypes" => ["PAYPAL"]
                    ]
                ],
                "returnPolicies" => [
                    [
                        "returnsAccepted" => true,
                        "returnPeriod" => [
                            "value" => 30,
                            "unit" => "DAY"
                        ]
                    ]
                ]
            ],
            "listing" => [
                "listingPolicies" => [
                    "fulfillmentPolicyId" => $fulfillmentPolicyId,
                    "paymentPolicyId" => $paymentPolicyId,
                    "returnPolicyId" => $returnPolicyId
                ],
                "locationSettings" => [
                    "countryCode" => "US",
                    "region" => "CA",
                    "city" => "San Jose",
                    "postalCode" => "95131"
                ]
            ],
            "categoryId" => $categoryId,
            "merchantLocationKey" => $merchantLocationKey
        ]; */

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
        Log::info("data to craete offer ::",[$offerData]);

        // 4. Make Offer API Call
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US'
        ])->post('https://api' . $this->ebayEnvType . 'ebay.com/sell/inventory/v1/offer', $offerData);
        Log::info("offer created successfully ::",[$response->json()]);
        Log::info("offer created successfully with offer id::".$response->json()['offerId'],[$response->json()]);
        return ($response->successful()) ? $response->json():false;
    }

    /**
     * Get Inventory Item from eBay
     */
    private function getInventoryItemOne($sku)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/inventory_item/{$sku}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Get Fulfillment Policy ID
     */
    private function getFulfillmentPolicy($marketplaceId)
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
    private function getPaymentPolicy($marketplaceId)
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
    private function getReturnPolicy($marketplaceId)
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
    private function getMerchantLocation()
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/inventory/v1/location");

        //echo '<pre>';print_r($response->json());

        return $response->successful() ? $response->json()['locations'][0]['merchantLocationKey'] ?? null : null;
    }


    /**
     * Get eBay Default Category Tree ID
     *
     * @OA\Get(
     *     path="/api/ebay/get-category-tree-id/{marketplace_id}",
     *     summary="Get eBay Category Tree ID",
     *     description="Fetches the default category tree ID for a given marketplace",
     *     tags={"eBay"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Parameter(
     *         name="marketplace_id",
     *         in="path",
     *         required=true,
     *         description="Marketplace ID (e.g., EBAY_US)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="categoryTreeId", type="string", example="0")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid request data")
     *         )
     *     )
     * )
     */
    public function getCategoryTreeId($marketplace_id)
    {
        $endpoint = "https://api" . $this->ebayEnvType . "ebay.com/commerce/taxonomy/v1/get_default_category_tree_id?marketplace_id={$marketplace_id}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get($endpoint);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch category tree ID'], 400);
    }

    /**
     * Get eBay Category List
     *
     * @OA\Get(
     *     path="/api/ebay/get-category-list/{category_tree_id}",
     *     summary="Get eBay Category List",
     *     description="Fetches the eBay category list for a given category tree ID",
     *     tags={"eBay"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Parameter(
     *         name="category_tree_id",
     *         in="path",
     *         required=true,
     *         description="Category Tree ID (retrieved using get-category-tree-id API)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="categoryId", type="string", example="123"),
     *                 @OA\Property(property="categoryName", type="string", example="Electronics")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid request data")
     *         )
     *     )
     * )
     */
    public function getCategoryList($category_tree_id)
    {
        //$data = $this->getCategoryTreeId('EBAY_US');
        //$endpoint = "https://api".$this->ebayEnvType."ebay.com/commerce/taxonomy/v1/category_tree/".$data->categoryTreeId;
        $endpoint = "https://api" . $this->ebayEnvType . "ebay.com/commerce/taxonomy/v1/category_tree/" . $category_tree_id;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get($endpoint);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch category list'], 400);
    }

    /**
     * Publish an Offer on eBay using SKU
     *
     * @OA\Post(
     *     path="/api/ebay/publish-offer/{offerId}",
     *     summary="Create an eBay Offer",
     *     description="Dynamically fetches required data from eBay and creates an offer",
     *     tags={"eBay Offers"},
     *     @OA\Parameter(
     *         name="offerId",
     *         in="path",
     *         required=true,
     *         description="Offer ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer pulished successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="offerId", type="string", example="1234567890"),
     *             @OA\Property(property="status", type="string", example="SUCCESS")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to create offer")
     *         )
     *     )
     * )
     */
    public function publishEbayOffer($offerId)
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

        Log::info('response ::',[$response->json()]);

        return response()->json($response->json(), $response->status());
    }


    /**
     * get ebay Shipping Package Code
     *
     * @OA\Get(
     *     path="/api/ebay/getShippingPackageCode",
     *     summary="get ebay Shipping Package Code",
     *     tags={"eBay"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="get ebay Shipping Package Code",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="responses", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="sku", type="string"),
     *                 @OA\Property(property="status", type="integer"),
     *                 @OA\Property(property="response", type="object")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized - eBay Access Token not found"),
     *     @OA\Response(response=404, description="No Shipping Package found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function getShippingPackageCode()
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/sell/metadata/v1/marketplace/EBAY_US/shipping_package");

        return $response->successful() ? $response->json() : null;
    }

    private function getBigCommerceCategoryName($categoryId){
        $url = $this->baseUrl . '/catalog/categories/'.$categoryId;

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

    private function getBigCommerceBrandName($brandId){
        $url = $this->baseUrl . '/catalog/brands/'.$brandId;

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

    private function getBigCommerceProductDetailsBySKU($sku){
        $url = $this->baseUrl . '/catalog/products?sku='.$sku;

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

    /**
     * Get eBay Category id by categoryname
     *
     * @OA\Get(
     *     path="/api/ebay/get-category-id-by-name/{categoryName}",
     *     summary="Get eBay Category Id by category name",
     *     description="Fetches the eBay category id for a given category name",
     *     tags={"eBay"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\Parameter(
     *         name="categoryName",
     *         in="path",
     *         required=true,
     *         description="Category Name (retrieved using bigcommerce product categoroy API)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="categoryId", type="string", example="123")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid request data")
     *         )
     *     )
     * )
     */
    public function getCategoryIdFromEbay($categoryName){
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'application/json',
        ])->get("https://api" . $this->ebayEnvType . "ebay.com/commerce/taxonomy/v1/category_tree/0/get_category_suggestions?q=".$categoryName);
        
        //return $response->successful() ? $response->json() : null;
        if ($response->successful()) {
            return $response->json()->categorySuggestions[0]->category->categoryId;
        }else{
            return response()->json([
                'error' => 'Failed to fetch category',
                'message' => $response->body(),
            ], $response->status());
        }
        //return $response->successful() ? $response->json()->categorySuggestions[0]->category->categoryId : null;
    }

    private function getBigCommerceProductImages($productId){
        $url = $this->baseUrl . '/catalog/products/'.$productId.'/images';

        $response = Http::withHeaders($this->bigCommerceHeaders)->get($url);

        if ($response->successful()) {
            //return response()->json($response->json()['data']);
            $data = $response->json()['data'];
            $imageArr = [];
            foreach($data as $k => $v){
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
}
