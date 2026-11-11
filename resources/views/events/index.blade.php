@php
    $hasFilters = $filters['q'] || $filters['category'] || $filters['city'] || $filters['date_from'] || $filters['date_to'] || $filters['price_min'] !== null || $filters['price_max'] !== null;
@endphp

<x-layouts.public
    :title="$seo['title']"
    :description="$seo['description']"
    :settings="$settings"
    :canonical="route('events.index')"
>
    @push('styles')
        <style>
            .event-poster { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: var(--mint); }
            .event-poster img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
            .event-card:hover .event-poster img { transform: scale(1.04); }
            .event-badge { position: absolute; top: .85rem; right: .85rem; background: var(--teal); color: #fff; border-radius: 999px; padding: .3rem .75rem; font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
            .event-badge.badge-sold { background: #6c757d; }
            .filter-card { border: 1px solid var(--line); border-radius: 1rem; background: #fff;  }
            .category-chip { border: 1px solid var(--line); border-radius: 999px; padding: .45rem 1rem; color: var(--ink); text-decoration: none; font-weight: 600; font-size: .9rem; background: #fff; }
            .category-chip:hover, .category-chip.active { background: var(--teal); border-color: var(--teal); color: #fff; }
            .banner-slide { border-radius: 1.25rem; overflow: hidden; aspect-ratio: 21 / 8; background: var(--mint); }
            .banner-slide img { width: 100%; height: 100%; object-fit: cover; }
            .empty-state { border: 1px dashed var(--line); border-radius: 1.25rem; background: #fff; padding: 3.5rem 1.5rem; text-align: center; }
        </style>
    @endpush

    <section class="hero pb-4">
        <div class="container">
            <div class="row align-items-end g-4">
                <div class="col-lg-8">
                    <span class="eyebrow d-inline-block mb-3">Katalog event</span>
                    <h1 class="display-4 mb-3">Temukan pengalaman berikutnya.</h1>
                    <p class="lead text-secondary mb-0">Konser, seminar, workshop, dan festival pilihan. Pesan tiket online, terima e-tiket langsung.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="btn btn-ghost" href="{{ route('faq') }}"><i class="fa-regular fa-circle-question me-1"></i> Bantuan pembelian</a>
                </div>
            </div>
        </div>
    </section>

    @if ($banners->isNotEmpty())
        <section class="pb-4">
            <div class="container">
                <div id="eventBannerCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($banners as $banner)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                @php $target = $banner->link_url ?: ($banner->event ? route('events.show', $banner->event->slug) : null); @endphp
                                @if ($target)
                                    <a href="{{ $target }}" class="banner-slide d-block">
                                        <img src="{{ $banner->image_url }}" alt="Banner {{ $banner->event?->title ?? 'event' }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                                    </a>
                                @else
                                    <div class="banner-slide">
                                        <img src="{{ $banner->image_url }}" alt="Banner event" loading="lazy">
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($banners->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#eventBannerCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Sebelumnya</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#eventBannerCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Berikutnya</span>
                        </button>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="section pt-4">
        <div class="container">
            <div class="filter-card p-3 p-lg-4 mb-4">
                <form method="GET" action="{{ route('events.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold" for="filter-q">Cari event</label>
                        <input type="search" class="form-control" id="filter-q" name="q" value="{{ $filters['q'] }}" placeholder="Nama event, minimal 2 karakter">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="filter-category">Kategori</label>
                        <select class="form-select" id="filter-category" name="category">
                            <option value="">Semua</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected($filters['category'] === $category->slug)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="filter-city">Kota</label>
                        <select class="form-select" id="filter-city" name="city">
                            <option value="">Semua</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}" @selected($filters['city'] === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="filter-date-from">Dari tanggal</label>
                        <input type="date" class="form-control" id="filter-date-from" name="date_from" value="{{ $filters['date_from']?->format('Y-m-d') }}">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="filter-date-to">Sampai</label>
                        <input type="date" class="form-control" id="filter-date-to" name="date_to" value="{{ $filters['date_to']?->format('Y-m-d') }}">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="filter-price-min">Harga min</label>
                        <input type="number" min="0" step="1000" class="form-control" id="filter-price-min" name="price_min" value="{{ $filters['price_min'] }}" placeholder="0">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="filter-price-max">Harga max</label>
                        <input type="number" min="0" step="1000" class="form-control" id="filter-price-max" name="price_max" value="{{ $filters['price_max'] }}" placeholder="Tanpa batas">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="filter-sort">Urutkan</label>
                        <select class="form-select" id="filter-sort" name="sort">
                            <option value="upcoming" @selected($filters['sort'] === 'upcoming')>Terdekat</option>
                            <option value="price_asc" @selected($filters['sort'] === 'price_asc')>Harga terendah</option>
                            <option value="price_desc" @selected($filters['sort'] === 'price_desc')>Harga tertinggi</option>
                            <option value="newest" @selected($filters['sort'] === 'newest')>Terbaru</option>
                        </select>
                    </div>
                    <div class="col-lg-4 d-flex gap-2">
                        <button type="submit" class="btn btn-main flex-grow-1"><i class="fa-solid fa-magnifying-glass me-1"></i> Terapkan</button>
                        <a href="{{ route('events.index') }}" class="btn btn-ghost">Reset</a>
                    </div>
                </form>
            </div>

            @if ($categories->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="{{ route('events.index', array_filter(['q' => $filters['q'], 'city' => $filters['city']])) }}" class="category-chip {{ ! $filters['category'] ? 'active' : '' }}">Semua kategori</a>
                    @foreach ($categories as $category)
                        <a href="{{ route('events.index', array_filter(['category' => $category->slug, 'q' => $filters['q'], 'city' => $filters['city']])) }}" class="category-chip {{ $filters['category'] === $category->slug ? 'active' : '' }}">{{ $category->name }}</a>
                    @endforeach
                </div>
            @endif

            @if (! $hasFilters && $featured->isNotEmpty())
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <div class="eyebrow">Pilihan editor</div>
                        <h2 class="h3 mb-0">Event unggulan</h2>
                    </div>
                </div>
                <div class="row g-4 mb-5">
                    @foreach ($featured->take(3) as $event)
                        <div class="col-md-6 col-lg-4">
                            <x-events.card :event="$event" />
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <div class="eyebrow">Semua event</div>
                    <h2 class="h3 mb-0">{{ $hasFilters ? 'Hasil pencarian' : 'Event mendatang' }}</h2>
                </div>
                <div class="text-secondary small">{{ $events->total() }} event</div>
            </div>

            @if ($events->isEmpty())
                <div class="empty-state">
                    <div class="icon-box mx-auto mb-3"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <h3 class="h5">Tidak ada event yang cocok</h3>
                    <p class="text-secondary mb-3">Coba ubah kata kunci, hapus beberapa filter, atau lihat semua event yang tersedia.</p>
                    <a href="{{ route('events.index') }}" class="btn btn-main">Reset filter</a>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($events as $event)
                        <div class="col-md-6 col-lg-4">
                            <x-events.card :event="$event" />
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
