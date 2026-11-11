<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Organizer\OrganizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OrganizerService $service): View
    {
        $organizer = $service->forUser($request->user());

        Gate::authorize('view', $organizer);

        $events = $organizer->events()
            ->with(['category', 'venue'])
            ->orderByDesc('start_at')
            ->limit(5)
            ->get();

        $counts = $organizer->events()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('organizer.dashboard', [
            'organizer' => $organizer,
            'events' => $events,
            'counts' => $counts,
            'publishedCount' => (int) ($counts[Event::STATUS_PUBLISHED] ?? 0) + (int) ($counts[Event::STATUS_SOLD_OUT] ?? 0),
        ]);
    }
}
