<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The following settings determine how Cross-Origin Resource Sharing (CORS)
    | requests are handled. You may adjust these settings as needed.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'email/verify/*', 
        'email/verification-notification-guest',
    ],

    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, etc.)

    'allowed_origins' => [
        'http://localhost:3000', 
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // If you use Sanctum with cookies, set this to true

];
?>
