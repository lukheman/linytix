<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketTypeSalesRequest;
use App\Models\Event;
use App\Models\TicketType;
use App\Services\Organizer\TicketTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketTypeController extends Controller
{
    public function updateSales(
        TicketTypeSalesRequest $request,
        Event $event,
        TicketType $ticketType,
        TicketTypeService $tickets,
    ): RedirectResponse {
        abort_unless($ticketType->event_id === $event->getKey(), 404);

        Gate::authorize('manageTickets', $event);

        $tickets->updateSales($ticketType, $request->validated(), $request->user(), $request->ip());

        return back()->with('success', 'Kuota dan jadwal penjualan tiket berhasil diperbarui.');
    }
}
