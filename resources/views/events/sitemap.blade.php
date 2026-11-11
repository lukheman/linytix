<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('events.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    @foreach (['faq', 'info.about', 'info.privacy', 'info.terms', 'info.refund'] as $route)
        <url>
            <loc>{{ route($route) }}</loc>
            <changefreq>monthly</changefreq>
            <priority>0.5</priority>
        </url>
    @endforeach
    @foreach ($events as $event)
        <url>
            <loc>{{ route('events.show', $event->slug) }}</loc>
            <lastmod>{{ $event->updated_at?->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
</urlset>
