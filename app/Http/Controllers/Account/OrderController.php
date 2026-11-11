<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\SiteContent;
use App\Support\TicketPdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with('event')
            ->latest()
            ->paginate(10);

        return view('account.orders.index', [
            'orders' => $orders,
            'settings' => SiteContent::settings(),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'event.venue',
            'items.ticketType',
            'payments' => fn ($query) => $query->latest(),
            'tickets.ticketType',
        ]);

        return view('account.orders.show', [
            'order' => $order,
            'settings' => SiteContent::settings(),
        ]);
    }

    public function pdf(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $order->loadMissing(['event.venue', 'tickets.ticketType']);

        abort_if($order->tickets->isEmpty(), 404);

        return TicketPdf::download($order);
    }
}
