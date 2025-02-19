<?php

// Override Laravel's storage path to use /tmp which is writable on Vercel
if (!defined('LARAVEL_START')) {
    define('LARAVEL_START', microtime(true));
}

// Setting a custom, writable storage path
$app = require __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath('/tmp/storage');

// Make sure temporary storage directories exist
$paths = [
    '/tmp/storage/app',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs'
];

foreach ($paths as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

return $app;