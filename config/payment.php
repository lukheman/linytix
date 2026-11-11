<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gateway aktif
    |--------------------------------------------------------------------------
    |
    | Gunakan "fake" untuk development/test dan "midtrans" untuk sandbox atau
    | produksi. Jangan pernah menaruh kredensial langsung di file ini.
    |
    */
    'default' => env('PAYMENT_GATEWAY', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Batas waktu pembayaran
    |--------------------------------------------------------------------------
    */
    'order_expiry_minutes' => (int) env('PAYMENT_ORDER_EXPIRY_MINUTES', 15),

    'fake' => [
        'secret' => env('PAYMENT_FAKE_SECRET', 'fake-secret'),
    ],

    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'snap_base_url' => env('MIDTRANS_SNAP_BASE_URL', 'https://app.sandbox.midtrans.com/snap/v1'),
        'api_base_url' => env('MIDTRANS_API_BASE_URL', 'https://api.sandbox.midtrans.com'),
    ],
];
