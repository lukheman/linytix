<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use App\Services\Organizer\EventService;
use App\Services\Organizer\OrganizerService;
use App\Services\Organizer\SalesReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request, OrganizerService $organizers): View
    {
        $organizer = $organizers->forUser($request->user());

        Gate::authorize('view', $organizer);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $events = $organizer->events()
            ->with(['category', 'venue'])
            ->withCount(['ticketTypes as active_ticket_types_count' => fn ($query) => $query->where('is_active', true)])
            ->withSum('ticketTypes as ticket_quota_total', 'quota')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('start_at')
            ->paginate(12)
            ->withQueryString();

        $counts = $organizer->events()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('organizer.events.index', [
            'organizer' => $organizer,
            'events' => $events,
            'counts' => $counts,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(OrganizerService $organizers): View
    {
        $organizer = $organizers->forUser(request()->user());

        Gate::authorize('create', Event::class);

        return view('organizer.events.create', $this->formData($organizer));
    }

    public function store(
        StoreEventRequest $request,
        OrganizerService $organizers,
        EventService $events,
    ): RedirectResponse {
        $organizer = $organizers->forUser($request->user());

        Gate::authorize('create', Event::class);

        $event = $events->createDraft(
            $organizer,
            $request->user(),
            $request->validated(),
            $request->file('poster'),
            $request->file('banner'),
            $request->file('gallery', []),
            $request->boolean('lineups_present') ? ($request->all('lineups')['lineups'] ?? []) : [],
            $request->ip(),
        );

        return redirect()
            ->route('organizer.events.edit', $event)
            ->with('success', 'Event draft berhasil dibuat. Lengkapi tiket lalu ajukan review.');
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        $event->load(['category', 'venue', 'images', 'lineups', 'ticketTypes']);

        return view('organizer.events.edit', array_merge(
            $this->formData($event->organizer),
            ['event' => $event],
        ));
    }

    public function update(
        UpdateEventRequest $request,
        Event $event,
        EventService $events,
    ): RedirectResponse {
        Gate::authorize('update', $event);

        $validated = $request->validated();

        $events->update(
            $event,
            $request->user(),
            $validated,
            $request->file('poster'),
            $request->file('banner'),
            $request->file('gallery', []),
            $request->boolean('lineups_present') ? ($request->all('lineups')['lineups'] ?? []) : null,
            $validated['remove_images'] ?? [],
            $request->ip(),
        );

        $message = $event->isFullyEditable()
            ? 'Event berhasil diperbarui.'
            : 'Deskripsi dan galeri event berhasil diperbarui.';

        return back()->with('success', $message);
    }

    public function destroy(Event $event, EventService $events): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $events->delete($event, request()->user(), request()->ip());

        return redirect()
            ->route('organizer.events.index')
            ->with('success', 'Event draft berhasil dihapus.');
    }

    public function submit(Event $event, EventService $events): RedirectResponse
    {
        Gate::authorize('submitForReview', $event);

        $events->submitForReview($event, request()->user(), request()->ip());

        return back()->with('success', 'Event berhasil diajukan untuk review admin.');
    }

    public function report(Request $request, Event $event, SalesReportService $reports): View
    {
        Gate::authorize('viewReport', $event);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'ticket_type_id' => ['nullable', 'uuid'],
        ]);

        $ticketTypeId = $validated['ticket_type_id'] ?? null;

        if ($ticketTypeId && ! $event->ticketTypes()->whereKey($ticketTypeId)->exists()) {
            abort(404);
        }

        $report = $reports->forEvent($event, [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'ticket_type_id' => $ticketTypeId,
        ]);

        return view('organizer.events.report', [
            'event' => $event,
            'report' => $report,
            'ticketTypes' => $event->ticketTypes()->orderBy('price')->get(['id', 'name', 'price']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Organizer $organizer): array
    {
        return [
            'organizer' => $organizer,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'venues' => Venue::query()->where('is_active', true)->orderBy('name')->get(),
            'timezones' => [
                'Asia/Jakarta' => 'WIB — Asia/Jakarta',
                'Asia/Makassar' => 'WITA — Asia/Makassar',
                'Asia/Jayapura' => 'WIT — Asia/Jayapura',
                'Asia/Singapore' => 'SGT — Asia/Singapore',
                'UTC' => 'UTC',
            ],
        ];
    }
}
