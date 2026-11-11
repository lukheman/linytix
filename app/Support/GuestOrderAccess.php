<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class GuestOrderAccess
{
    /**
     * Signed URL berlaku sampai 30 hari setelah event berakhir.
     */
    public static function expiresAt(Order $order): Carbon
    {
        $event = $order->event;
        $base = $event?->end_at
            ?? $event?->start_at
            ?? $order->created_at
            ?? now();

        return $base->copy()->addDays(30);
    }

    public static function urlFor(Order $order): string
    {
        return URL::temporarySignedRoute(
            'guest.orders.show',
            self::expiresAt($order),
            ['order' => $order->getKey()],
        );
    }

    public static function checkoutUrlFor(Order $order): string
    {
        return URL::temporarySignedRoute(
            'checkout.show',
            self::expiresAt($order),
            ['order' => $order->getKey()],
        );
    }

    public static function paymentUrlFor(Order $order): string
    {
        return URL::temporarySignedRoute(
            'checkout.pay',
            self::expiresAt($order),
            ['order' => $order->getKey()],
        );
    }

    public static function statusUrlFor(Order $order): string
    {
        return URL::temporarySignedRoute(
            'checkout.status',
            self::expiresAt($order),
            ['order' => $order->getKey()],
        );
    }

    public static function pdfUrlFor(Order $order): string
    {
        return URL::temporarySignedRoute(
            'guest.orders.pdf',
            self::expiresAt($order),
            ['order' => $order->getKey()],
        );
    }
}
