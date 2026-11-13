<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Services\Organizer\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('moderate', Event::class);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $events = Event::query()
            ->with(['organizer', 'category', 'venue'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhereHas('organizer', fn ($organizer) => $organizer->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending_review' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $counts = Event::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.events', [
            'events' => $events,
            'counts' => $counts,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function show(Event $event): View
    {
        Gate::authorize('moderate', Event::class);

        $event->load([
            'organizer.owner',
            'category',
            'venue',
            'ticketTypes',
            'images',
            'lineups',
            'approver',
        ])->loadCount([
            'orders',
            'orders as paid_orders_count' => fn ($query) => $query->where('status', Order::STATUS_PAID),
        ]);

        return view('admin.event-detail', [
            'event' => $event,
        ]);
    }

    public function approve(Request $request, Event $event, EventService $events): RedirectResponse
    {
        Gate::authorize('moderate', Event::class);

        $events->approve($event, $request->user(), $request->ip());

        return back()->with('success', 'Event "'.$event->title.'" berhasil dipublikasikan.');
    }

    public function reject(Request $request, Event $event, EventService $events): RedirectResponse
    {
        Gate::authorize('moderate', Event::class);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $events->reject($event, $request->user(), $validated['rejection_reason'], $request->ip());

        return back()->with('success', 'Event "'.$event->title.'" ditolak dan dikembalikan ke organizer.');
    }
}
