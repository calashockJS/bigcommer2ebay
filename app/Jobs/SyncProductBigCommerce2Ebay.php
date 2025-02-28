<?php

namespace App\Jobs;

use App\Services\EbaySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductBigCommerce2Ebay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sku;
    /**
     * Create a new job instance.
     */
    public function __construct($sku)
    {
        $this->sku = $sku;
    }

    /**
     * Execute the job.
     */
    public function handle(EbaySyncService $ebaySyncService): void
    {
        try{
            Log::channel('stderr')->info("Sync started for SKU: {$this->sku}");
            Log::channel('stderr')->info('now going to call $ebaySyncService->syncProductToEbay() with '.$this->sku);
            $ebaySyncService->syncProductToEbay($this->sku);
        }catch(\Exception $e){
            Log::channel('stderr')->info("Error syncing SKU: {$this->sku} - " . $e->getMessage());
        }
    }
}
