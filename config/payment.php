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

    /*
    |--------------------------------------------------------------------------
    | Xendit
    |--------------------------------------------------------------------------
    |
    | Payment Sessions API dipakai sebagai hosted payment page (mode
    | PAYMENT_LINK). Refund memakai Refund API v3 sehingga setiap request
    | wajib menyertakan header api-version. Secret key dan callback token
    | hanya boleh berasal dari environment. Token callback bersifat statis
    | (bukan HMAC) sehingga wajib dibandingkan secara ketat.
    |
    */
    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
        'is_production' => (bool) env('XENDIT_IS_PRODUCTION', false),
        'api_base_url' => env('XENDIT_API_BASE_URL', 'https://api.xendit.co'),
        // Xendit menolak expires_at kurang dari 10 menit di masa depan.
        'session_duration_minutes' => (int) env('XENDIT_SESSION_DURATION_MINUTES', 15),
        'refund_api_version' => env('XENDIT_REFUND_API_VERSION', '2024-11-11'),
        'methods' => [
            'qris' => ['QRIS'],
            'bank_transfer' => [
                'BCA_VIRTUAL_ACCOUNT',
                'BNI_VIRTUAL_ACCOUNT',
                'BRI_VIRTUAL_ACCOUNT',
                'MANDIRI_VIRTUAL_ACCOUNT',
                'PERMATA_VIRTUAL_ACCOUNT',
            ],
            'gopay' => ['GOPAY'],
            'shopeepay' => ['SHOPEEPAY'],
        ],
    ],
];
