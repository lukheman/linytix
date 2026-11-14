<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use App\Support\PublicCache;
use App\Support\SiteContent;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Halaman beranda: discovery event dan pintu masuk pembelian tiket.
     */
    public function __invoke(): View
    {
        $settings = SiteContent::settings();

        $eventRelations = [
            'organizer',
            'category',
            'venue',
            'ticketTypes' => fn ($query) => $query->where('is_active', true),
        ];

        $featured = Event::query()
            ->visible()
            ->featured()
            ->with($eventRelations)
            ->orderBy('start_at')
            ->limit(6)
            ->get();

        $upcoming = Event::query()
            ->visible()
            ->with($eventRelations)
            ->orderBy('start_at')
            ->limit(8)
            ->get();

        $categories = PublicCache::remember(PublicCache::HOME_CATEGORIES, fn () => Category::query()
            ->where('is_active', true)
            ->whereHas('events', fn ($query) => $query->visible())
            ->withCount(['events' => fn ($query) => $query->visible()])
            ->orderBy('sort_order')
            ->limit(8)
            ->get());

        $cities = PublicCache::remember(PublicCache::CITIES, fn () => Venue::query()
            ->where('is_active', true)
            ->whereHas('events', fn ($query) => $query->visible())
            ->orderBy('city')
            ->pluck('city')
            ->filter()
            ->unique()
            ->values());

        $siteName = $settings['site_name'] ?? 'LinyTix';

        return view('guest.landing-page', [
            'featured' => $featured,
            'upcoming' => $upcoming,
            'categories' => $categories,
            'cities' => $cities,
            'settings' => $settings,
            'stats' => [
                'events' => Event::query()->visible()->count(),
                'cities' => $cities->count(),
                'categories' => $categories->count(),
                'organizers' => Organizer::query()
                    ->whereHas('events', fn ($query) => $query->visible())
                    ->count(),
            ],
            'seo' => [
                'title' => $siteName.' — Beli Tiket Event di Indonesia',
                'description' => 'Temukan dan beli tiket konser, seminar, workshop, festival, serta event online dari berbagai kota di Indonesia. Dapatkan e-tiket QR langsung melalui email.',
                'jsonLd' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $siteName,
                    'url' => route('home'),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('events.index').'?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ]);
    }
}
