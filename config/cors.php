<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration — PAM Swadaya
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Daftarkan secara eksplisit Origin Frontend Development (Vue/React Vite)
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://127.0.0.1:5173',
        'http://localhost:3000',
    ],

    'allowed_origins_patterns' => [],

    // Mengizinkan pengiriman header Authorization (Bearer Token) & Content-Type
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Simpan cache jawaban Preflight OPTIONS di browser selama 1 jam (3600 detik)
    'max_age' => 3600,

    // Karena kita menggunakan Stateless Bearer Token (bukan Cookie/Session), set false
    'supports_credentials' => false,

];