<?php

use App\Http\Controllers\api\ApiController;
use App\Http\Controllers\api\EbayPolicyController;
use App\Http\Controllers\api\EbayProductController;
use App\Http\Controllers\api\EbayAuthController;
use App\Http\Controllers\EbayWebAuthController;
use App\Http\Controllers\WebHookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/bigcommerce/show-bc-sku', [ApiController::class, 'showSkuFromJSONFile']);
Route::get('/bigcommerce/show-bc-sku-1', [WebHookController::class, 'showSkuFromJSONFile']);
//Route::middleware('auth')->group(function(){
    Route::get('/ebay/bc-sku-to-ebay-listing/{bcsku}', [ApiController::class, 'createEbayProductWithBCSkuWeb']);
//});
Route::get('/ebay/bc-sku-remove/{bcsku}', [ApiController::class, 'removeSkuFromJSONFile']);
Route::get('/ebay/bc-sku-remove-1/{bcsku}', [WebHookController::class, 'removeSkuFromJSONFileWeb']);

Route::get('/ebay/auth', [EbayWebAuthController::class, 'redirectToEbay']); // Redirect user
Route::get('/ebay/callback', [EbayWebAuthController::class, 'handleEbayCallback']); // Handle callback
Route::get('/bc2ebay/sync-products',[ApiController::class,'getSyncProducts']);

