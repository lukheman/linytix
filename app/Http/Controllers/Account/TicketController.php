<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Support\QrCode;
use App\Support\SiteContent;
use App\Support\TicketPdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_REFUNDED]))
            ->with(['event', 'ticketType', 'order'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('event_id');

        return view('account.tickets.index', [
            'groupedTickets' => $tickets,
            'settings' => SiteContent::settings(),
        ]);
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['event.venue', 'ticketType', 'order']);

        return view('account.tickets.show', [
            'ticket' => $ticket,
            'qrSvg' => QrCode::svg($ticket->ticket_code),
            'settings' => SiteContent::settings(),
        ]);
    }

    public function pdf(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return TicketPdf::download($ticket->order);
    }
}
