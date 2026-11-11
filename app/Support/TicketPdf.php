<?php

namespace App\Support;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;

class TicketPdf
{
    public static function forOrder(Order $order): DomPdfDocument
    {
        $order->loadMissing(['event.venue', 'tickets.ticketType']);

        return Pdf::loadView('tickets.pdf', [
            'order' => $order,
            'tickets' => $order->tickets,
            'siteName' => SiteContent::settings()['site_name'] ?? 'LinyTix',
        ])->setPaper('a4');
    }

    public static function output(Order $order): string
    {
        return self::forOrder($order)->output();
    }

    public static function download(Order $order)
    {
        return self::forOrder($order)->download('e-tiket-'.$order->order_number.'.pdf');
    }
}
