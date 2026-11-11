<?php

namespace App\Exceptions;

use RuntimeException;

class LatePaymentStockUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Stok tiket tidak tersedia untuk pembayaran yang terlambat.');
    }
}
