@php
    $start = $event->startsAtLocal();
    $end = $event->endsAtLocal();
    $soldOut = $event->isSoldOut();
    $hasAvailableTicket = $event->ticketTypes->contains(fn ($type) => $type->isOnSaleNow());
    $mapQuery = $event->venue?->maps_url ?: ($event->venue?->coordinates() ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($event->venue->coordinates()) : null);
@endphp

<x-layouts.public
    :title="$seo['title']"
    :description="$seo['description']"
    :og-image="$seo['ogImage'] ?? null"
    :og-type="$seo['ogType'] ?? 'website'"
    :canonical="$seo['canonical'] ?? null"
    :json-ld="$seo['jsonLd'] ?? null"
    :settings="$settings"
>
    @push('styles')
        <style>
            .detail-hero { padding: 8.5rem 0 3rem; background: var(--canvas); }
            .detail-poster { border-radius: 1.25rem; overflow: hidden; aspect-ratio: 4 / 3; background: var(--mint);  }
            .detail-poster img { width: 100%; height: 100%; object-fit: cover; }
            .meta-pill { display: inline-flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid var(--line); border-radius: 999px; padding: .5rem .95rem; font-weight: 600; font-size: .875rem; }
            .ticket-row { border: 1px solid var(--line); border-radius: 1rem; padding: 1rem 1.15rem; background: #fff; }
            .ticket-row + .ticket-row { margin-top: .75rem; }
            .gallery-item { aspect-ratio: 4 / 3; overflow: hidden; border-radius: .9rem; background: var(--mint); }
            .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
            .sticky-card { position: sticky; top: 6.5rem; }
            .lineup-avatar { width: 3.2rem; height: 3.2rem; border-radius: 50%; background: var(--mint); color: var(--teal); display: grid; place-items: center; font-weight: 800; }
        </style>
    @endpush

    <section class="detail-hero">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('events.index') }}">Event</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('events.index', ['category' => $event->category?->slug]) }}">{{ $event->category?->name ?? 'Semua' }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $event->title }}</li>
                </ol>
            </nav>
            <div class="row g-4 g-lg-5 align-items-center">
                <div class="col-lg-5">
                    <div class="detail-poster">
                        <img src="{{ $event->displayPosterUrl() }}" alt="Poster {{ $event->title }}" fetchpriority="high">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if ($event->category)
                            <span class="badge rounded-pill text-bg-light border">{{ $event->category->name }}</span>
                        @endif
                        @if ($event->is_online)
                            <span class="badge rounded-pill text-bg-light border"><i class="fa-solid fa-wifi me-1"></i>Online</span>
                        @endif
                        @if ($soldOut)
                            <span class="badge rounded-pill text-bg-secondary">Tiket habis</span>
                        @endif
                    </div>
                    <h1 class="display-4 mb-3">{{ $event->title }}</h1>
                    <p class="lead text-secondary mb-4">{{ $event->short_desc ?: str($event->description)->limit(220) }}</p>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="meta-pill"><i class="fa-regular fa-calendar text-success"></i>{{ $start?->format('d M Y') }}</span>
                        <span class="meta-pill"><i class="fa-regular fa-clock text-success"></i>{{ $start?->format('H.i') }} – {{ $end?->format('H.i') }} {{ $start?->format('T') }}</span>
                        <span class="meta-pill"><i class="fa-solid fa-location-dot text-success"></i>{{ $event->is_online ? 'Online' : ($event->venue?->name ?? 'Lokasi menyusul') }}</span>
                    </div>

                    <div class="d-flex flex-wrap align-items-end gap-3">
                        <div>
                            <div class="small text-secondary">Harga mulai</div>
                            <div class="display-6 text-success">{{ $event->lowestActivePrice() > 0 ? 'Rp '.number_format($event->lowestActivePrice(), 0, ',', '.') : 'Gratis' }}</div>
                        </div>
                        <a href="#tiket" class="btn btn-main btn-lg {{ $soldOut ? 'disabled' : '' }}" @if ($soldOut) aria-disabled="true" @endif>
                            <i class="fa-solid fa-ticket me-1"></i> {{ $soldOut ? 'Tiket habis' : 'Pilih tiket' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section pt-4">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="mb-5">
                        <h2 class="h3 mb-3">Tentang event</h2>
                        <div class="text-secondary" style="white-space: pre-line;">{{ $event->description ?: 'Deskripsi event belum tersedia.' }}</div>
                    </div>

                    @if ($event->lineups->isNotEmpty())
                        <div class="mb-5">
                            <h2 class="h3 mb-3">Lineup & pembicara</h2>
                            <div class="row g-3">
                                @foreach ($event->lineups as $lineup)
                                    <div class="col-sm-6">
                                        <div class="card-clean p-3 d-flex align-items-center gap-3 h-100">
                                            <div class="lineup-avatar">{{ str($lineup->name)->substr(0, 1)->upper() }}</div>
                                            <div>
                                                <div class="fw-semibold">{{ $lineup->name }}</div>
                                                <div class="small text-secondary">{{ $lineup->perform_at?->format('d M Y, H.i') ?? 'Jadwal menyusul' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($event->images->isNotEmpty())
                        <div class="mb-5">
                            <h2 class="h3 mb-3">Galeri</h2>
                            <div class="row g-3">
                                @foreach ($event->images as $image)
                                    <div class="col-sm-6">
                                        <figure class="gallery-item mb-0">
                                            <img src="{{ $image->image_url }}" alt="{{ $image->caption ?: 'Galeri '.$event->title }}" loading="lazy">
                                        </figure>
                                        @if ($image->caption)
                                            <div class="small text-secondary mt-1">{{ $image->caption }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mb-5">
                        <h2 class="h3 mb-3">Lokasi</h2>
                        @if ($event->is_online)
                            <div class="card-clean p-4">
                                <div class="fw-semibold mb-1">Event online</div>
                                <p class="text-secondary mb-0">Tautan akses akan dikirim setelah pembayaran berhasil. Demi keamanan, tautan tidak ditampilkan di halaman publik.</p>
                            </div>
                        @elseif ($event->venue)
                            <div class="card-clean p-4">
                                <div class="fw-semibold">{{ $event->venue->name }}</div>
                                <p class="text-secondary mb-2">{{ $event->venue->address }}, {{ $event->venue->city }}, {{ $event->venue->province }}</p>
                                @if ($mapQuery)
                                    <a href="{{ $mapQuery }}" target="_blank" rel="noopener" class="text-link"><i class="fa-solid fa-map-location-dot me-1"></i> Buka peta lokasi</a>
                                @endif
                            </div>
                        @else
                            <p class="text-secondary">Lokasi event akan diumumkan.</p>
                        @endif
                    </div>

                    <div>
                        <h2 class="h3 mb-3">Syarat & ketentuan</h2>
                        <div class="card-clean p-4 text-secondary" style="white-space: pre-line;">{{ $event->terms ?: 'Tiket yang sudah dibeli tidak dapat dikembalikan kecuali event dibatalkan oleh penyelenggara.' }}</div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="sticky-card">
                        <div class="card-clean p-4" id="tiket">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Pilih tiket</h2>
                                <span class="small text-secondary">{{ $event->remainingQuota() }} tersisa</span>
                            </div>

                            @forelse ($event->ticketTypes as $ticketType)
                                <div class="ticket-row">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $ticketType->name }}</div>
                                            @if ($ticketType->description)
                                                <div class="small text-secondary">{{ $ticketType->description }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">{{ $ticketType->price > 0 ? 'Rp '.number_format($ticketType->price, 0, ',', '.') : 'Gratis' }}</div>
                                            <div class="small text-secondary">{{ $ticketType->saleStatusLabel() }}</div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                                        <div class="small text-secondary">
                                            Sisa {{ $ticketType->remainingQuota() }} tiket
                                            @if ($ticketType->sale_start_at)
                                                · Jual mulai {{ $ticketType->sale_start_at->format('d M Y, H.i') }}
                                            @endif
                                        </div>
                                        @if ($ticketType->isOnSaleNow())
                                            <a class="btn btn-sm btn-main" href="{{ route('checkout.create', ['event' => $event->slug, 'ticket_type' => $ticketType->getKey()]) }}">
                                                Pilih <i class="fa-solid fa-arrow-right ms-1"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-secondary" disabled>{{ $ticketType->saleStatusLabel() }}</button>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-secondary mb-0">Belum ada jenis tiket yang dijual.</p>
                            @endforelse

                            <div class="d-grid mt-3">
                                @if ($soldOut || ! $hasAvailableTicket)
                                    <button class="btn btn-secondary btn-lg" disabled>Tiket habis</button>
                                @else
                                    <a class="btn btn-main btn-lg" href="{{ route('checkout.create', $event->slug) }}">
                                        <i class="fa-solid fa-cart-shopping me-1"></i> Beli tiket
                                    </a>
                                    <div class="small text-secondary text-center mt-2">Pilih jenis tiket dan isi data pembeli pada halaman berikutnya.</div>
                                @endif
                            </div>
                        </div>

                        @if ($event->organizer)
                            <div class="card-clean p-4 mt-4">
                                <div class="small text-secondary mb-1">Diselenggarakan oleh</div>
                                <a href="{{ route('organizers.show', $event->organizer->slug) }}" class="fw-semibold text-decoration-none">{{ $event->organizer->name }}</a>
                                @if ($event->organizer->is_verified)
                                    <div class="small text-success mt-1"><i class="fa-solid fa-circle-check me-1"></i>Organizer terverifikasi</div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section section-soft">
            <div class="container">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <div class="eyebrow">Event serupa</div>
                        <h2 class="h3 mb-0">Mungkin Anda juga suka</h2>
                    </div>
                    <a href="{{ route('events.index') }}" class="text-link">Lihat semua</a>
                </div>
                <div class="row g-4">
                    @foreach ($related as $relatedEvent)
                        <div class="col-md-6 col-lg-4">
                            <x-events.card :event="$relatedEvent" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
