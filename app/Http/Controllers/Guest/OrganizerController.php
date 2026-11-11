<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Models\OrganizerSlugRedirect;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizerController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        $organizer = Organizer::query()
            ->where('slug', $slug)
            ->first();

        if (! $organizer) {
            $redirect = OrganizerSlugRedirect::query()
                ->with('organizer')
                ->where('slug', $slug)
                ->first();

            abort_unless($redirect && $redirect->organizer, 404);

            return redirect()->route('organizers.show', $redirect->organizer->slug, 301);
        }

        $events = $organizer->events()
            ->visible()
            ->with(['category', 'venue', 'ticketTypes' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('start_at')
            ->paginate(12);

        $settings = SiteContent::settings();

        return view('guest.organizers.show', [
            'organizer' => $organizer,
            'events' => $events,
            'settings' => $settings,
            'seo' => [
                'title' => $organizer->name.' — Event',
                'description' => $organizer->description
                    ? str($organizer->description)->limit(155)->toString()
                    : 'Event yang diselenggarakan oleh '.$organizer->name.'.',
            ],
        ]);
    }
}
