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
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'email/verification-notification',
        'verify-email/*',
        'api/v1/logout',
    ],

    'allowed_methods' => ['*'],

    //'allowed_origins' => array_map('trim', explode(',', env('FRONTEND_URL'))),
    'allowed_origins' =>  [
        'https://fe-sipb.crulxproject.com',
        'https://sipb.crulxproject.com',
        'http://localhost:5173',
        'http://localhost',
    ],

    'allowed_origins_patterns' => ['/^https:\/\/.*\.crulxproject\.com$/'],

    'allowed_headers' => [
        'X-XSRF-TOKEN',
        'X-CSRF-TOKEN',
        'Content-Type',
        'Accept',
        'Authorization',
        'Origin',
        'X-Requested-With',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
