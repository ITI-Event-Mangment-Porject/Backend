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
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],    'allowed_origins' => [
        env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'),
        'http://localhost:3000',  // In case you run on default React port
        'http://127.0.0.1:5173', // Alternative localhost notation
        'http://127.0.0.1:3000'  // Alternative localhost notation
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Authorization',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];