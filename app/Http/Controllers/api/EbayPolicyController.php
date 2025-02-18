<?php

namespace App\Http\Controllers\api;


use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;


class EbayPolicyController extends Controller
{
    private $client;
    private $authToken;
    private $marketplaceId;
    private $baseUrl;
    private $accessToken, $ebayEnvType;

    public function __construct(Request $request)
    {
        // Get environment type value from .env
        $envTypeEbay = env('EBAY_ENV_TYPE');
        $this->ebayEnvType = $envTypeEbay;

        $ebayAccessToken = $request->accessToken;
        if ($ebayAccessToken == '') {
            if($envTypeEbay=='.sandbox.'){
                // Get eBay Access Token from .env
                $ebayAccessToken = env('EBAY_ACCESS_TOKEN_SANDBOX');
            }else{
                $ebayAccessToken = env('EBAY_ACCESS_TOKEN');
            }
        }

        $this->client = new Client();
        $this->authToken = $ebayAccessToken; //env('EBAY_AUTH_TOKEN');
        $this->marketplaceId = env('EBAY_MARKETPLACE_ID', 'EBAY_US');

        $this->baseUrl = 'https://api'.$this->ebayEnvType.'ebay.com/sell/account/v1/';
    }

    /**
     * @OA\Post(
     *     path="/api/ebay/fulfillment-policy",
     *     summary="Create eBay Fulfillment Policy",
     *     tags={"eBay Policies"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "handlingTime"},
     *             @OA\Property(property="name", type="string", example="Standard Fulfillment"),
     *             @OA\Property(property="handlingTime", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=400, description="Invalid Request"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function createFulfillmentPolicy(Request $request)
    {
        $url = $this->baseUrl . 'fulfillment_policy';

        $body = [
            'name' => $request->input('name'),
            'marketplaceId' => $this->marketplaceId,
            'handlingTime' => [
                'unit' => 'DAY',
                'value' => $request->input('handlingTime', 2)
            ],
            'shippingOptions' => [
                [
                    'optionType' => 'INTERNATIONAL', // Fix: Added `optionType`  DOMESTIC
                    'costType' => 'FLAT_RATE',
                    'shippingServices' => [
                        [
                            'shippingCarrierCode' => 'UPS',
                            'shippingServiceCode' => 'UPS_GROUND',
                            'freeShipping' => true
                        ]
                    ]
                ]
            ]
        ];
        echo json_encode($body);die;
        return $this->sendRequest($url, $body);
    }

    /**
     * @OA\Post(
     *     path="/api/ebay/payment-policy",
     *     summary="Create eBay Payment Policy",
     *     tags={"eBay Policies"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "paymentMethods"},
     *             @OA\Property(property="name", type="string", example="Credit Card Payments"),
     *             @OA\Property(
     *                 property="paymentMethods",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"CREDIT_CARD", "PAYPAL"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=400, description="Invalid Request"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function createPaymentPolicy(Request $request)
    {
        $url = $this->baseUrl . 'payment_policy';

        $body = [
            'name' => $request->input('name'),
            'marketplaceId' => $this->marketplaceId,
            'paymentMethods' => array_map(function ($method) {
                return ['paymentMethodType' => $method];
            }, $request->input('paymentMethods', ['CREDIT_CARD']))
        ];

        return $this->sendRequest($url, $body);
    }

    /**
     * @OA\Post(
     *     path="/api/ebay/return-policy",
     *     summary="Create eBay Return Policy",
     *     tags={"eBay Policies"},
     *     @OA\Parameter(
     *         name="accessToken",
     *         in="query",
     *         description="User Token denerated at eBay Developer site.",
     *         required=false,
     *         @OA\Schema(type="string", default="")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "returnsAccepted"},
     *             @OA\Property(property="name", type="string", example="Standard Return Policy"),
     *             @OA\Property(property="returnsAccepted", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=400, description="Invalid Request"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function createReturnPolicy(Request $request)
    {
        $url = $this->baseUrl . 'return_policy';

        $body = [
            'name' => $request->input('name'),
            'marketplaceId' => $this->marketplaceId,
            'returnsAccepted' => $request->input('returnsAccepted', true),
            'returnPeriod' => [
                'unit' => 'DAY',
                'value' => 30
            ],
            'returnShippingCostPayer' => 'BUYER'
        ];

        return $this->sendRequest($url, $body);
    }

    private function sendRequest($url, $body)
    {
        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authToken,
                    'Content-Type' => 'application/json',
                    'X-EBAY-C-MARKETPLACE-ID' => $this->marketplaceId
                ],
                'json' => $body
            ]);

            return response()->json(json_decode($response->getBody()), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
