<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batas pesanan menunggu pembayaran
    |--------------------------------------------------------------------------
    |
    | Jumlah maksimum order pending yang boleh dimiliki satu identitas
    | (user atau email pembeli) dalam waktu bersamaan.
    |
    */
    'max_pending_orders_per_identity' => (int) env('TICKETING_MAX_PENDING_ORDERS', 2),

    /*
    |--------------------------------------------------------------------------
    | Komponen biaya order
    |--------------------------------------------------------------------------
    |
    | Nilai default 0 agar total yang dibayar pembeli sama dengan harga tiket.
    | Biaya payment gateway dihitung terpisah saat metode pembayaran dipilih.
    |
    */
    'admin_fee' => (int) env('TICKETING_ADMIN_FEE', 0),

    'tax_percent' => (float) env('TICKETING_TAX_PERCENT', 0),

    /*
    |--------------------------------------------------------------------------
    | Metode pembayaran
    |--------------------------------------------------------------------------
    |
    | Kode metode diteruskan ke gateway. Label dipakai di halaman checkout.
    |
    */
    'payment_methods' => [
        'qris' => 'QRIS',
        'bank_transfer' => 'Transfer Bank',
        'gopay' => 'GoPay',
        'shopeepay' => 'ShopeePay',
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload media event
    |--------------------------------------------------------------------------
    |
    | Semua media event disimpan pada disk yang sama. Batas ukuran dalam KB dan
    | dimensi minimum mengikuti kebutuhan poster, banner, galeri, dan lineup.
    |
    */
    'event_uploads' => [
        'disk' => env('TICKETING_EVENT_UPLOAD_DISK', 'public'),
        'gallery_max' => 8,
        'max_kb' => [
            'poster' => 4096,
            'banner' => 4096,
            'gallery' => 2048,
            'lineup' => 2048,
        ],
        'dimensions' => [
            'poster' => ['min_width' => 600, 'min_height' => 400],
            'banner' => ['min_width' => 1200, 'min_height' => 400],
            'gallery' => ['min_width' => 400, 'min_height' => 300],
            'lineup' => ['min_width' => 200, 'min_height' => 200],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limit per menit
    |--------------------------------------------------------------------------
    |
    | Nilai dapat dinaikkan sementara di lingkungan load test melalui env.
    | Jangan menaikkan batas produksi tanpa analisis kapasitas.
    |
    */
    'rate_limits' => [
        'checkout_store' => (int) env('TICKETING_RATE_LIMIT_CHECKOUT_STORE', 10),
        'checkout_pay' => (int) env('TICKETING_RATE_LIMIT_CHECKOUT_PAY', 10),
        'checkout_status' => (int) env('TICKETING_RATE_LIMIT_CHECKOUT_STATUS', 120),
        'ticket_scan' => (int) env('TICKETING_RATE_LIMIT_SCAN', 60),
        'payment_webhook' => (int) env('TICKETING_RATE_LIMIT_PAYMENT_WEBHOOK', 120),
    ],
];
