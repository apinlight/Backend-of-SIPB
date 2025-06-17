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
        'profile',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://fe-sipb.crulxproject.com',
        'https://sipb.crulxproject.com',
        'http://127.0.0.2:5173',
        'http://127.0.0.1:80',
    ],

    'allowed_origins_patterns' => ['/^https:\/\/.*\.crulxproject\.com$/'],

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
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language', 
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
        'Set-Cookie',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
