<?php

use Illuminate\Support\Facades\Route;

// Root endpoint - JSON response for API-only backend
Route::get('/', function () {
    return response()->json([
        'service' => 'SIPB API',
        'version' => '1.0',
        'status' => 'online',
        'message' => 'API is running. See documentation on GitHub.',
        'endpoints' => [
            'health' => '/api/v1/health',
            'online' => '/api/v1/online',
            'documentation' => 'https://github.com/apinlight/backend'
        ]
    ]);
});

// ✅ Include auth routes (API routes under /api/v1)
require __DIR__.'/auth.php';
