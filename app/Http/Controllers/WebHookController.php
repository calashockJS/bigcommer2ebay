<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;


class WebHookController extends Controller
{
    public function getSKUByWebhook(Request $request)
    {
        $filePath = 'big-commerce-sku.json';
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

        // Validate bcsku header
        if (!$request->input('data.id')) {
            Log::channel('stderr')->info('now in ApiController at getSKUByWebhook() and not getting data.id in webhook from big commerce');
            Log::info('now in ApiController at getSKUByWebhook() and not getting data.id in webhook from big commerce');
            return response()->json([
                'message' => 'BC product is required',
                'error' => 'Missing required product id'
            ], 401);
        }

        $bcProductId = $request->input('data.id');

        // Check if security key is blank
        if (empty($bcProductId)) {
            return response()->json([
                'message' => 'BC Sku cannot be empty',
                'error' => 'Invalid header value'
            ], 401);
        }

        //$this->createEbayProductWithBCSku($bcsku);

        if (Storage::exists($filePath)) {
            $jsonData = json_decode(Storage::get($filePath), true);
            if (!is_array($jsonData)) {
                $jsonData = [];
            }
        } else {
            $jsonData = [];
        }

        if (!in_array($bcProductId, $jsonData)) {
            $jsonData[] = $bcProductId;
            Storage::put($filePath, json_encode($jsonData, JSON_PRETTY_PRINT));
        }

        return response()->json(['message' => 'SKU updated successfully', 'data' => $jsonData]);
    }

    public function showSkuFromJSONFile()
    {
        $filePath = 'big-commerce-sku.json';

        if (!Storage::exists($filePath)) {
            return view('sku_list', ['skus' => []]);
        }

        $jsonData = json_decode(Storage::get($filePath), true);
        if (!is_array($jsonData)) {
            $jsonData = [];
        }

        return view('sku_list_local', ['skus' => $jsonData]);
    }

    public function removeSkuFromJSONFileWeb($sku)
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
            return (int) $item !== (int) $sku;
        });

        Storage::put($filePath, json_encode(array_values($jsonData), JSON_PRETTY_PRINT));

        //return response()->json(['message' => 'SKU removed successfully', 'data' => $jsonData]);
        return redirect('/bigcommerce/show-bc-sku-1')->with(['msg' => 'SKU removed successfully.']);
    }
}
