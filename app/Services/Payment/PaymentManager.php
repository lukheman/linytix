<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentManager
{
    public function gateway(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('payment.default', 'fake');

        return match ($name) {
            'fake' => app(FakeGateway::class),
            'midtrans' => app(MidtransGateway::class),
            default => throw new InvalidArgumentException("Payment gateway [{$name}] tidak dikenal."),
        };
    }
}
