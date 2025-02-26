<?php

use App\Http\Controllers\api\ApiController;
use App\Http\Controllers\api\EbayPolicyController;
use App\Http\Controllers\api\EbayProductController;
use App\Http\Controllers\api\EbayAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



/***
 * 
 * Fetch first 10 products (default)
 * GET /api/bigcommerce/products
 * 
 * 
 * Fetch 5 products starting from the 10th product
 * GET /api/bigcommerce/products?limit=5&offset=10
 * 
 * Fetch 20 products starting from the 50th product
 * GET /api/bigcommerce/products?limit=20&offset=50
 * 
 */
Route::get('/bigcommerce/products', [ApiController::class, 'getProducts']);
Route::get('/bigcommerce/getProductsAndSaveJson', [ApiController::class, 'getProductsAndSaveJson']);
Route::post('/bigcommerce/products', [ApiController::class, 'createProduct']);
Route::get('/bigcommerce/products/{id}', [ApiController::class, 'getProductById']);
Route::put('/bigcommerce/products/{id}', [ApiController::class, 'updateProduct']);
Route::delete('/bigcommerce/products/{id}', [ApiController::class, 'deleteProduct']);
Route::get('/bigcommerce/show-bc-sku', [ApiController::class, 'showSkuFromJSONFile']);
Route::post('/middleware-webhook/sku', [ApiController::class, 'getSKUByWebhook']);

Route::get('/ebay/inventory-item/{sku}', [ApiController::class, 'getInventoryItem']);
Route::get('/ebay/create-inventory', [ApiController::class, 'createEbayProduct']);
Route::post('/ebay/bc-sku-to-ebay-listing/{bcsku}', [ApiController::class, 'createEbayProductWithBCSku']);
Route::get('/ebay/get-inventory-items', [ApiController::class, 'getAllEbayInventoryItems']);
Route::post('/ebay/create-offer/{sku}', [ApiController::class, 'createOffer']);
Route::post('/ebay/publish-offer/{offerId}', [ApiController::class, 'publishEbayOffer']);

Route::get('/ebay/generate-access-token', [ApiController::class, 'generateEbayAccessToken']);
Route::get('/ebay/get-category-tree-id/{marketplace_id}', [ApiController::class, 'getCategoryTreeId']);
Route::get('/ebay/get-category-list/{category_tree_id}', [ApiController::class, 'getCategoryList']);
Route::get('/ebay/getShippingPackageCode', [ApiController::class, 'getShippingPackageCode']);
Route::get('/ebay/get-category-id-by-name/{categoryName}', [ApiController::class, 'getCategoryIdFromEbay']);

Route::post('/ebay/fulfillment-policy', [EbayPolicyController::class, 'createFulfillmentPolicy']);
Route::post('/ebay/payment-policy', [EbayPolicyController::class, 'createPaymentPolicy']);
Route::post('/ebay/return-policy', [EbayPolicyController::class, 'createReturnPolicy']);

Route::get('/ebay/auth', [EbayAuthController::class, 'redirectToEbay']); // Redirect user
Route::get('/ebay/callback', [EbayAuthController::class, 'handleEbayCallback']); // Handle callback
Route::get('/ebay/token', [EbayAuthController::class, 'getUserAccessToken']); // Get access token
//Route::get('/ebay/cli-token', [EbayAuthController::class, 'getAppAccessToken']);
Route::get('/ebay/cli-token', [EbayAuthController::class, 'automatedEbayAuth']);

Route::get('/ebay/cli-token-1', [EbayAuthController::class, 'fetchNewAppToken']);