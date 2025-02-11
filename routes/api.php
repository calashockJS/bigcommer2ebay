<?php

use App\Http\Controllers\api\ApiController;
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

Route::get('/ebay/inventory-item/{sku}', [ApiController::class, 'getInventoryItem']);
Route::get('/ebay/create-inventory', [ApiController::class, 'createEbayProduct']);
Route::get('/ebay/get-inventory-items', [ApiController::class, 'getAllEbayInventoryItems']);
Route::post('/ebay/create-offer', [ApiController::class, 'createOffer']);

Route::get('/ebay/generate-access-token', [ApiController::class, 'generateEbayAccessToken']);
Route::get('/ebay/get-category-tree-id/{marketplace_id}', [ApiController::class, 'getCategoryTreeId']);
Route::get('/ebay/get-category-list/{category_tree_id}', [ApiController::class, 'getCategoryList']);