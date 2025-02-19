<?php

// Include Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap the application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Adjust the request URI for Vercel deployment
if ($_SERVER['REQUEST_URI'] !== '/' && file_exists(__DIR__ . '/../public' . $_SERVER['REQUEST_URI'])) {
    return false;
}

// If using api prefix routes, fix the request path to prevent double /api/api
if (strpos($_SERVER['REQUEST_URI'], '/api/api/') === 0) {
    $_SERVER['REQUEST_URI'] = substr_replace($_SERVER['REQUEST_URI'], '/api/', 0, 9);
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);