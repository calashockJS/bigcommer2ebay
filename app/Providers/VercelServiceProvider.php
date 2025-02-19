<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class VercelServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Use tmp directory for writable storage on Vercel
        if (env('APP_ENV') === 'production') {
            $this->app->useStoragePath('/tmp/storage');
        }
    }

    public function boot()
    {
        // Create necessary directories
        if (env('APP_ENV') === 'production') {
            $storage_path = '/tmp/storage';
            if (!is_dir($storage_path)) {
                mkdir($storage_path, 0755, true);
                mkdir($storage_path . '/app', 0755, true);
                mkdir($storage_path . '/framework', 0755, true);
                mkdir($storage_path . '/framework/cache', 0755, true);
                mkdir($storage_path . '/framework/sessions', 0755, true);
                mkdir($storage_path . '/framework/views', 0755, true);
                mkdir($storage_path . '/logs', 0755, true);
            }
        }
    }
}