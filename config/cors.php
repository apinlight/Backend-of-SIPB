<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        // ✅ DISABLED: Let our custom middleware handle all CORS for API routes
        // 'api/*',
        'sanctum/csrf-cookie',
        // 'login',
        // 'logout',
        // 'register',
        // 'forgot-password',
        // 'reset-password',
        // 'email/verification-notification',
        // 'verify-email/*',
        // 'api/v1/logout',
        // 'profile',
    ],

    'allowed_methods' => ['*'],

'allowed_origins' => [
        'https://fe-sipb.crulxproject.com',
        'https://sipb.crulxproject.com',
        'https://www.sipb.crulxproject.com',
        // ✅ CONDITIONAL: Allow local development ports
        ...env('APP_ENV', 'production') !== 'production' ? [
            'http://127.0.0.2:5173',
            'http://127.0.0.1:5173',
            'http://localhost:5173',
            'http://127.0.0.1:80',
            'http://localhost:80',
            // 🔥 ADD THESE NEW LINES:
            'http://127.0.0.1:8080',
            'http://localhost:8080', 
        ] : [],
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.crulxproject\.com$/',
        ...env('APP_ENV', 'production') !== 'production' ? [
            '/^http:\/\/127\.0\.0\.[0-9]+:[0-9]+$/',
            '/^http:\/\/localhost:[0-9]+$/',
        ] : [],
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'Origin',
        'Cache-Control',
        'Pragma',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
        'Cookie',
        // ✅ API versioning headers
        'X-API-Version',
        'X-Client-Version',
        'User-Agent',
        'Accept-Language',
        'Accept-Encoding',
        'Connection',
        'Host',
        'Referer',
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
        'Set-Cookie',
        // ✅ API response headers
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-API-Version',
        'X-Request-ID',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => false, // ✅ Let our custom middleware handle this

];
