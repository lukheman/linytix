<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventFilterRequest;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use App\Support\PublicCache;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(EventFilterRequest $request): View
    {
        $filters = $request->filters();

        $events = $this->filteredQuery($request, $filters)->paginate(12)->withQueryString();

        $banners = PublicCache::remember(PublicCache::BANNERS, fn () => Banner::query()
            ->activeNow()
            ->with('event')
            ->orderBy('sort_order')
            ->limit(5)
            ->get()
            ->filter(fn (Banner $banner) => $banner->event === null || $banner->event->isOnSale())
            ->values());

        $featured = Event::query()
            ->visible()
            ->featured()
            ->with(['organizer', 'category', 'venue', 'ticketTypes' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('start_at')
            ->limit(8)
            ->get();

        return view('events.index', [
            'events' => $events,
            'banners' => $banners,
            'featured' => $featured,
            'categories' => $this->categories(),
            'cities' => $this->cities(),
            'filters' => $filters,
            'settings' => SiteContent::settings(),
            'seo' => [
                'title' => 'Jelajahi Event — '.$this->siteName(),
                'description' => 'Temukan konser, seminar, workshop, dan festival terbaru. Beli tiket online dengan pembayaran aman.',
            ],
        ]);
    }

    public function show(Request $request, Event $event): View
    {
        abort_unless($event->isOnSale(), 404);

        $event->load([
            'organizer',
            'category',
            'venue',
            'images',
            'lineups',
            'ticketTypes' => fn ($query) => $query->where('is_active', true)->orderBy('price'),
        ]);

        $event->increment('view_count');

        $related = Event::query()
            ->visible()
            ->whereKeyNot($event->getKey())
            ->when($event->category_id, fn ($query) => $query->where('category_id', $event->category_id))
            ->with(['organizer', 'category', 'venue', 'ticketTypes' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('start_at')
            ->limit(3)
            ->get();

        return view('events.show', [
            'event' => $event,
            'related' => $related,
            'settings' => SiteContent::settings(),
            'seo' => [
                'title' => $event->title.' — '.$this->siteName(),
                'description' => $event->short_desc ?: str($event->description)->limit(155)->toString(),
                'ogImage' => $event->poster_url,
                'ogType' => 'article',
                'canonical' => route('events.show', $event->slug),
                'jsonLd' => $this->eventJsonLd($event),
            ],
        ]);
    }

    private function filteredQuery(EventFilterRequest $request, array $filters)
    {
        $query = Event::query()
            ->visible()
            ->with(['organizer', 'category', 'venue', 'ticketTypes' => fn ($relation) => $relation->where('is_active', true)]);

        if ($filters['q']) {
            $query->search($filters['q']);
        }

        if ($filters['category']) {
            $query->whereHas('category', fn ($relation) => $relation->where('slug', $filters['category']));
        }

        if ($filters['city']) {
            $query->whereHas('venue', fn ($relation) => $relation->where('city', $filters['city']));
        }

        if ($filters['date_from']) {
            $query->where('start_at', '>=', $filters['date_from']->startOfDay());
        }

        if ($filters['date_to']) {
            $query->where('start_at', '<=', $filters['date_to']->endOfDay());
        }

        if ($filters['price_min'] !== null) {
            $query->whereHas('ticketTypes', fn ($relation) => $relation
                ->where('is_active', true)
                ->where('price', '>=', $filters['price_min']));
        }

        if ($filters['price_max'] !== null) {
            $query->whereHas('ticketTypes', fn ($relation) => $relation
                ->where('is_active', true)
                ->where('price', '<=', $filters['price_max']));
        }

        match ($filters['sort']) {
            'price_asc' => $query->orderBy('min_price'),
            'price_desc' => $query->orderByDesc('min_price'),
            'newest' => $query->orderByDesc('published_at'),
            default => $query->orderBy('start_at'),
        };

        return $query;
    }

    private function categories()
    {
        return PublicCache::remember(PublicCache::CATEGORIES, fn () => Category::query()
            ->where('is_active', true)
            ->whereHas('events', fn ($query) => $query->visible())
            ->orderBy('sort_order')
            ->get());
    }

    private function cities()
    {
        return PublicCache::remember(PublicCache::CITIES, fn () => Venue::query()
            ->where('is_active', true)
            ->whereHas('events', fn ($query) => $query->visible())
            ->orderBy('city')
            ->pluck('city')
            ->filter()
            ->unique()
            ->values());
    }

    private function siteName(): string
    {
        return SiteContent::settings()['site_name'] ?? 'LinyTix';
    }

    /**
     * @return array<string, mixed>
     */
    private function eventJsonLd(Event $event): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => $event->short_desc ?: str($event->description)->limit(200)->toString(),
            'startDate' => $event->start_at?->toIso8601String(),
            'endDate' => $event->end_at?->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => $event->is_online
                ? 'https://schema.org/OnlineEventAttendanceMode'
                : 'https://schema.org/OfflineEventAttendanceMode',
            'url' => route('events.show', $event->slug),
            'organizer' => [
                '@type' => 'Organization',
                'name' => $event->organizer?->name ?? $this->siteName(),
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $event->lowestActivePrice(),
                'priceCurrency' => 'IDR',
                'availability' => $event->isSoldOut()
                    ? 'https://schema.org/SoldOut'
                    : 'https://schema.org/InStock',
                'url' => route('events.show', $event->slug),
            ],
        ];

        if (! $event->is_online && $event->venue) {
            $data['location'] = [
                '@type' => 'Place',
                'name' => $event->venue->name,
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $event->venue->address,
                    'addressLocality' => $event->venue->city,
                    'addressRegion' => $event->venue->province,
                    'addressCountry' => 'ID',
                ],
            ];
        }

        if ($event->poster_url) {
            $data['image'] = [$event->poster_url];
        }

        return $data;
    }
}
