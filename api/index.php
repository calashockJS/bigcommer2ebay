<?php

// Load custom bootstrap to handle Vercel's read-only filesystem
$app = require __DIR__ . '/bootstrap.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);