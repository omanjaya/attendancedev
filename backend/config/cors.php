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

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*', 'login'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Set via CORS_ALLOWED_ORIGINS environment variable.
    | For multiple origins, use comma-separated values:
    | CORS_ALLOWED_ORIGINS=https://example.com,https://app.example.com
    |
    | In development, you can add localhost origins:
    | CORS_ALLOWED_ORIGINS=https://absensi.patra.co.id,http://localhost:5173
    |
    */
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'https://absensi.patra.co.id')))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
