<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Scan\TicketScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ScanController extends Controller
{
    /**
     * Daftar event yang dapat discan oleh staff.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $events = Event::query()
                ->with('venue')
                ->whereIn('status', [Event::STATUS_PUBLISHED, Event::STATUS_SOLD_OUT])
                ->orderBy('start_at')
                ->get();
        } else {
            $events = $user->eventAssignments()
                ->with('venue')
                ->orderBy('start_at')
                ->get();
        }

        return view('scan.index', [
            'events' => $events,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Pintu masuk scan tiket.
     */
    public function show(Request $request, Event $event): View
    {
        Gate::authorize('scan', $event);

        $assignment = $event->staff()->whereKey($request->user()->getKey())->first();

        return view('scan.show', [
            'event' => $event->loadMissing('venue'),
            'gate' => $assignment?->pivot?->gate,
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * Validasi kode tiket di server. Client hanya mengirim kode.
     */
    public function store(
        Request $request,
        Event $event,
        TicketScanService $scans,
    ): JsonResponse|RedirectResponse {
        Gate::authorize('scan', $event);

        $validated = $request->validate([
            'ticket_code' => ['required', 'string', 'max:255'],
        ], [
            'ticket_code.required' => 'Kode tiket wajib diisi.',
        ]);

        $user = $request->user();
        $assignment = $event->staff()->whereKey($user->getKey())->first();

        $result = $scans->scan(
            $event,
            $validated['ticket_code'],
            $user,
            $assignment?->pivot?->gate,
            $request->ip(),
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with('scan_result', $result);
    }
}
