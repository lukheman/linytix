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
];
