<?php

use App\Http\Controllers\api\ApiController;
use App\Http\Controllers\api\EbayPolicyController;
use App\Http\Controllers\api\EbayProductController;
use App\Http\Controllers\api\EbayAuthController;
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
//Route::middleware('auth')->group(function(){
    Route::get('/ebay/bc-sku-to-ebay-listing/{bcsku}', [ApiController::class, 'createEbayProductWithBCSkuWeb']);
//});

