<?php
return [
    'server_key' => env('MIDTRANS_SERVER_KEY', 'your-server-key'),
    'client_key' => env('MIDTRANS_CLIENT_KEY', 'your-client-key'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false), // false = sandbox, true = production
    'sanitized' => env('MIDTRANS_SANITIZED', true),
    '3ds' => env('MIDTRANS_3DS', true),
];
