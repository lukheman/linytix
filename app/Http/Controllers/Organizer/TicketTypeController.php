<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketTypeRequest;
use App\Models\Event;
use App\Models\TicketType;
use App\Services\Organizer\TicketTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TicketTypeController extends Controller
{
    public function index(Event $event): View
    {
        Gate::authorize('manageTickets', $event);

        $event->load(['ticketTypes' => fn ($query) => $query->orderBy('price')]);

        return view('organizer.events.tickets', [
            'event' => $event,
        ]);
    }

    public function store(TicketTypeRequest $request, Event $event, TicketTypeService $tickets): RedirectResponse
    {
        Gate::authorize('manageTickets', $event);

        $tickets->create($event, $request->validated(), $request->user(), $request->ip());

        return back()->with('success', 'Jenis tiket berhasil ditambahkan.');
    }

    public function update(
        TicketTypeRequest $request,
        Event $event,
        TicketType $ticketType,
        TicketTypeService $tickets,
    ): RedirectResponse {
        $this->ensureBelongsToEvent($event, $ticketType);

        Gate::authorize('manageTickets', $event);

        $tickets->update($ticketType, $request->validated(), $request->user(), $request->ip());

        return back()->with('success', 'Jenis tiket berhasil diperbarui.');
    }

    public function destroy(Event $event, TicketType $ticketType, TicketTypeService $tickets): RedirectResponse
    {
        $this->ensureBelongsToEvent($event, $ticketType);

        Gate::authorize('manageTickets', $event);

        $tickets->delete($ticketType, request()->user(), request()->ip());

        return back()->with('success', 'Jenis tiket berhasil dihapus.');
    }

    private function ensureBelongsToEvent(Event $event, TicketType $ticketType): void
    {
        abort_unless($ticketType->event_id === $event->getKey(), 404);
    }
}
