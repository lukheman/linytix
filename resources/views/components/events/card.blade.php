@props(['event'])

@php
    $soldOut = $event->isSoldOut();
    $start = $event->startsAtLocal();
    $lowest = $event->lowestActivePrice();
@endphp

<article class="card-clean event-card h-100 overflow-hidden">
    <a href="{{ route('events.show', $event->slug) }}" class="text-decoration-none text-reset d-block">
        <div class="event-poster">
            <img src="{{ $event->displayPosterUrl() }}" alt="Poster {{ $event->title }}" loading="lazy">
            <span class="event-badge {{ $soldOut ? 'badge-sold' : '' }}">
                {{ $soldOut ? 'Habis' : 'Tersedia' }}
            </span>
        </div>
    </a>
    <div class="p-4 d-flex flex-column h-100">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
            @if ($event->category)
                <span class="badge rounded-pill text-bg-light border">{{ $event->category->name }}</span>
            @endif
            @if ($event->is_online)
                <span class="badge rounded-pill text-bg-light border"><i class="fa-solid fa-wifi me-1"></i>Online</span>
            @endif
        </div>
        <h3 class="h5 mb-2">
            <a class="text-decoration-none text-reset" href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a>
        </h3>
        <div class="text-secondary small mb-1">
            <i class="fa-regular fa-calendar me-2"></i>
             {{ $start?->translatedFormat('d M Y, H.i') }} {{ $start?->format('T') }}
        </div>
        <div class="text-secondary small mb-3">
            <i class="fa-solid fa-location-dot me-2"></i>
            {{ $event->is_online ? 'Online' : ($event->venue?->city ?? 'Lokasi menyusul') }}
        </div>
        <div class="mt-auto d-flex justify-content-between align-items-end gap-2">
            <div>
                <div class="small text-secondary">Mulai dari</div>
                <div class="fw-bold fs-5">{{ $lowest > 0 ? 'Rp '.number_format($lowest, 0, ',', '.') : 'Gratis' }}</div>
            </div>
            <span class="btn btn-sm btn-main">Detail</span>
        </div>
    </div>
</article>
