@php
    $siteName = $settings['site_name'] ?? 'LinyTix';
    $heroEyebrow = $settings['hero_eyebrow'] ?? 'Event di seluruh Indonesia';
    $heroTitle = $settings['hero_title'] ?? 'Temukan event di seluruh Indonesia, beli tiketnya, atau daftarkan eventmu.';
    $heroSubtitle = $settings['hero_subtitle'] ?? 'Cari konser, seminar, workshop, festival, dan event lainnya berdasarkan kota, kategori, atau tanggal. Pesan tiket online dan terima e-tiket QR langsung di email.';
    $heroNote = $settings['hero_note'] ?? 'E-tiket QR · Akses pesanan tanpa akun · Informasi event transparan';

    $heroEvent = $featured->first() ?? $upcoming->first();
    $highlighted = $featured->isNotEmpty() ? $featured->take(3) : $upcoming->take(3);
    $upcomingEvents = $upcoming->take(6);
    $heroPrice = $heroEvent?->lowestActivePrice() ?? 0;
@endphp

<x-layouts.public
    :title="$seo['title']"
    :description="$seo['description']"
    :settings="$settings"
    :canonical="route('home')"
    :jsonLd="$seo['jsonLd']"
>
    <div class="home-page">
        <section class="hero home-hero">
            <div class="container">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-lg-6">
                        <span class="eyebrow d-inline-block mb-2 mb-lg-3">{{ $heroEyebrow }}</span>
                        <h1 class="hero-title mb-3">{{ $heroTitle }}</h1>
                        <p class="lead text-secondary hero-subtitle mb-4">{{ $heroSubtitle }}</p>

                        <form method="GET" action="{{ route('events.index') }}" class="search-card p-3 p-md-4 mb-3" role="search">
                            <div class="search-query">
                                <label class="form-label small fw-semibold" for="home-q">Mau pergi ke event apa?</label>
                                <div class="input-group">
                                    <input type="search" class="form-control" id="home-q" name="q"
                                         placeholder="Cari event, artis, topik, atau kota">
                                    <button type="submit" class="btn btn-main search-submit" aria-label="Cari event">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span class="d-none d-sm-inline ms-2">Temukan</span>
                                    </button>
                                </div>
                            </div>

                            <button class="btn btn-filter w-100 mt-2 d-md-none" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#homeFilters" aria-expanded="false" aria-controls="homeFilters">
                                <i class="fa-solid fa-sliders me-1"></i> Filter kategori &amp; kota
                            </button>

                            <div class="collapse d-md-block mt-2 mt-md-3" id="homeFilters">
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold" for="home-category">Kategori</label>
                                        <select class="form-select" id="home-category" name="category">
                                            <option value="">Semua kategori</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold" for="home-city">Kota</label>
                                        <select class="form-select" id="home-city" name="city">
                                            <option value="">Semua kota</option>
                                            @foreach ($cities as $city)
                                                <option value="{{ $city }}">{{ $city }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="hero-meta">
                            <a href="{{ route('events.index') }}" class="text-link">
                                Semua event <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                            @if ($heroNote)
                                <span class="hero-note text-secondary">{{ $heroNote }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-6">
                        @if ($heroEvent)
                            <div class="hero-event-card card-clean">
                                <a href="{{ route('events.show', $heroEvent->slug) }}" class="hero-event-poster d-block">
                                    <img src="{{ $heroEvent->displayPosterUrl() }}" alt="Poster {{ $heroEvent->title }}"
                                         width="800" height="500" loading="eager" decoding="async">
                                    <span class="event-badge">Pilihan minggu ini</span>
                                </a>
                                <div class="hero-event-body">
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        @if ($heroEvent->category)
                                            <span class="badge rounded-pill text-bg-light border">{{ $heroEvent->category->name }}</span>
                                        @endif
                                        @if ($heroEvent->is_online)
                                            <span class="badge rounded-pill text-bg-light border">
                                                <i class="fa-solid fa-wifi me-1"></i>Online
                                            </span>
                                        @endif
                                    </div>
                                    <h2 class="hero-event-title mb-2">
                                        <a class="text-decoration-none text-reset" href="{{ route('events.show', $heroEvent->slug) }}">
                                            {{ $heroEvent->title }}
                                        </a>
                                    </h2>
                                    <div class="hero-event-meta text-secondary">
                                        <span>
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            {{ $heroEvent->startsAtLocal()?->translatedFormat('l, d F Y, H.i') }} {{ $heroEvent->startsAtLocal()?->format('T') }}
                                        </span>
                                        <span>
                                            <i class="fa-solid fa-location-dot me-1"></i>
                                            {{ $heroEvent->is_online ? 'Online' : ($heroEvent->venue?->name ?? 'Lokasi menyusul') }}
                                        </span>
                                    </div>
                                    <div class="hero-event-footer">
                                        <div>
                                            <div class="small text-secondary">Mulai dari</div>
                                            <div class="fw-bold fs-5">
                                                {{ $heroPrice > 0 ? 'Rp '.number_format($heroPrice, 0, ',', '.') : 'Gratis' }}
                                            </div>
                                        </div>
                                        <a href="{{ route('events.show', $heroEvent->slug) }}" class="btn btn-main hero-event-cta">
                                            Pilih tiket <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="card-clean p-4 text-center">
                                <div class="icon-box mx-auto mb-3"><i class="fa-regular fa-calendar-plus"></i></div>
                                <h2 class="h4">Event baru segera hadir</h2>
                                <p class="text-secondary mb-3">Katalog event sedang disiapkan. Cek kembali sebentar lagi.</p>
                                <a href="{{ route('events.index') }}" class="btn btn-main">Lihat katalog event</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if ($categories->isNotEmpty())
            <section class="section section-categories">
                <div class="container">
                    <div class="section-head-row">
                        <div class="section-head">
                            <span class="eyebrow">Kategori</span>
                            <h2 class="section-title mt-2 mb-2">Temukan event sesuai minatmu.</h2>
                            <p class="text-secondary mb-0">Pilih kategori dan mulai cari pengalaman berikutnya.</p>
                        </div>
                        <a class="text-link section-more" href="{{ route('events.index') }}">
                            Semua event <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <div class="category-scroller">
                        @foreach ($categories as $category)
                            @php
                                $categoryIcon = match ($category->slug) {
                                    'musik' => 'fa-music',
                                    'seminar' => 'fa-microphone-lines',
                                    'workshop' => 'fa-screwdriver-wrench',
                                    'festival' => 'fa-champagne-glasses',
                                    default => 'fa-calendar-star',
                                };
                            @endphp
                            <a href="{{ route('events.index', ['category' => $category->slug]) }}"
                                class="card-clean category-card d-flex align-items-center gap-3 h-100">
                                <div class="category-icon"><i class="fa-solid {{ $categoryIcon }}"></i></div>
                                <div class="min-w-0">
                                    <div class="category-label">{{ $category->name }}</div>
                                    <div class="category-count text-secondary">{{ $category->events_count }} event</div>
                                </div>
                            </a>
                        @endforeach
                        <a href="{{ route('events.index') }}" class="card-clean category-card d-flex align-items-center gap-3 h-100">
                            <div class="category-icon"><i class="fa-solid fa-arrow-right"></i></div>
                            <div class="min-w-0">
                                <div class="category-label">Semua event</div>
                                <div class="category-count text-secondary">Lihat katalog</div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>
        @endif

        @if ($highlighted->isNotEmpty())
            <section class="section">
                <div class="container">
                    <div class="section-head-row">
                        <div class="section-head">
                            <span class="eyebrow">Pilihan editor</span>
                            <h2 class="section-title mt-2 mb-2">Event unggulan</h2>
                            <p class="text-secondary mb-0">Jelajahi event pilihan dari berbagai kota dan kategori.</p>
                        </div>
                        <a class="text-link section-more" href="{{ route('events.index') }}">
                            Lihat semua event <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <div class="event-scroller">
                        @foreach ($highlighted as $event)
                            <div class="event-scroller-item">
                                <x-events.card :event="$event" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="section section-soft">
            <div class="container">
                <div class="section-head-row">
                    <div class="section-head">
                        <span class="eyebrow">Jadwal terdekat</span>
                        <h2 class="section-title mt-2 mb-2">Event mendatang</h2>
                        <p class="text-secondary mb-0">Lihat event berdasarkan tanggal, lokasi, dan kategori.</p>
                    </div>
                    <a class="text-link section-more" href="{{ route('events.index') }}">
                        Jelajahi semua <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>

                @if ($upcomingEvents->isEmpty())
                    <div class="card-clean p-4 text-center">
                        <div class="icon-box mx-auto mb-3"><i class="fa-regular fa-calendar-xmark"></i></div>
                        <h3 class="h5">Belum ada event mendatang</h3>
                        <p class="text-secondary mb-3">Event baru akan segera hadir. Sementara itu, jelajahi katalog atau baca panduan pembelian.</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('events.index') }}" class="btn btn-main">Jelajahi event</a>
                            <a href="{{ route('faq') }}" class="btn btn-ghost">Buka FAQ</a>
                        </div>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach ($upcomingEvents as $event)
                            <div class="col-12 col-md-6 col-lg-4">
                                <x-events.card :event="$event" variant="compact" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="section" id="cara-beli">
            <div class="container">
                <div class="section-head mb-4">
                    <span class="eyebrow">Cara beli tiket</span>
                    <h2 class="section-title mt-2 mb-2">Beli tiket dalam tiga langkah mudah.</h2>
                    <p class="text-secondary mb-0">Pilih event, selesaikan pembayaran, lalu siapkan e-tiket untuk hari acara.</p>
                </div>

                <div class="step-grid">
                    @foreach ([
                        ['number' => '01', 'icon' => 'fa-magnifying-glass', 'title' => 'Pilih event', 'text' => 'Lihat jadwal, lokasi, deskripsi, dan jenis tiket yang tersedia.'],
                        ['number' => '02', 'icon' => 'fa-credit-card', 'title' => 'Selesaikan pembayaran', 'text' => 'Pilih metode pembayaran yang tersedia dan cek detail pesanan sebelum membayar.'],
                        ['number' => '03', 'icon' => 'fa-qrcode', 'title' => 'Terima e-tiket', 'text' => 'E-tiket QR dikirim ke email dan dapat dibuka kembali melalui akun atau tautan aman.'],
                    ] as $step)
                        <div class="card-clean step-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="icon-box"><i class="fa-solid {{ $step['icon'] }}"></i></div>
                                <div class="step-number">{{ $step['number'] }}</div>
                            </div>
                            <h3 class="h6 mb-1">{{ $step['title'] }}</h3>
                            <p class="text-secondary mb-0">{{ $step['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section section-soft">
            <div class="container">
                <div class="row g-4 g-lg-5 align-items-center">
                    <div class="col-lg-4">
                        <span class="eyebrow">Untuk pelanggan</span>
                        <h2 class="section-title mt-2 mb-2">Semua yang kamu butuhkan untuk membeli tiket dengan tenang.</h2>
                        <p class="text-secondary">Cek detail event, selesaikan pembelian, dan akses e-tiket dari satu tempat.</p>
                        <a class="btn btn-main mt-1" href="{{ route('faq') }}">
                            <i class="fa-regular fa-circle-question me-1"></i> Baca bantuan
                        </a>
                    </div>
                    <div class="col-lg-8">
                        <div class="benefit-grid">
                            @foreach ([
                                ['icon' => 'fa-circle-info', 'title' => 'Informasi event jelas', 'text' => 'Cek jadwal, lokasi, kategori, harga, dan detail event sebelum membeli.'],
                                ['icon' => 'fa-qrcode', 'title' => 'E-tiket QR unik', 'text' => 'Setiap tiket punya kode QR unik yang divalidasi di lokasi event.'],
                                ['icon' => 'fa-envelope-circle-check', 'title' => 'Beli tanpa akun', 'text' => 'Selesaikan pembelian sebagai guest dan akses pesanan lewat tautan aman di email.'],
                                ['icon' => 'fa-rotate-left', 'title' => 'Ketentuan terbuka', 'text' => 'Baca syarat pembelian dan kebijakan refund sebelum menyelesaikan pesanan.'],
                            ] as $benefit)
                                <div class="card-clean benefit-card h-100">
                                    <div class="icon-box"><i class="fa-solid {{ $benefit['icon'] }}"></i></div>
                                    <h3 class="h6 mb-1">{{ $benefit['title'] }}</h3>
                                    <p class="text-secondary mb-0">{{ $benefit['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="untuk-organizer">
            <div class="container">
                <div class="row g-4 g-lg-5 align-items-center">
                    <div class="col-lg-5">
                        <span class="eyebrow">Untuk organizer</span>
                        <h2 class="section-title mt-2 mb-3">Punya event? Jual tiketnya dari satu platform.</h2>
                        <p class="text-secondary mb-3">
                            Daftarkan event Anda, kelola tiket dan kuota, pantau penjualan, lalu gunakan e-tiket QR untuk
                            check-in peserta dari berbagai kota di Indonesia.
                        </p>
                        <div class="organizer-actions">
                            @if (auth()->check() && auth()->user()->isOrganizer())
                                <a href="{{ route('organizer.dashboard') }}" class="btn btn-main">
                                    <i class="fas fa-gauge-high me-1"></i> Buka Dashboard Organizer
                                </a>
                            @else
                                <a href="{{ \App\Support\OrganizerCta::url() }}" class="btn btn-main">
                                    <i class="fas fa-building me-1"></i> Daftarkan event
                                </a>
                            @endif
                            <a href="{{ route('organizer.guide') }}" class="btn btn-ghost">Lihat panduan organizer</a>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="step-grid step-grid-4">
                            @foreach ([
                                ['number' => '01', 'icon' => 'fa-user-check', 'title' => 'Buat akun', 'text' => 'Daftar, verifikasi email, lalu ajukan profil organizer.'],
                                ['number' => '02', 'icon' => 'fa-shield-halved', 'title' => 'Ajukan profil', 'text' => 'Admin meninjau profil dan mengirim catatan jika ada perbaikan.'],
                                ['number' => '03', 'icon' => 'fa-calendar-plus', 'title' => 'Buat event & tiket', 'text' => 'Lengkapi detail event, unggah poster, dan tambahkan jenis tiket.'],
                                ['number' => '04', 'icon' => 'fa-rocket', 'title' => 'Review & tayang', 'text' => 'Ajukan event untuk review. Setelah disetujui, tiket dapat mulai dijual.'],
                            ] as $step)
                                <div class="card-clean step-card h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="icon-box"><i class="fa-solid {{ $step['icon'] }}"></i></div>
                                        <div class="step-number">{{ $step['number'] }}</div>
                                    </div>
                                    <h3 class="h6 mb-1">{{ $step['title'] }}</h3>
                                    <p class="text-secondary mb-0">{{ $step['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="cta-band">
                    <div class="row align-items-center g-3 g-lg-4">
                        <div class="col-lg-8">
                            <span class="eyebrow">Akses pesanan</span>
                            <h2 class="cta-title text-white mt-2 mb-2">Tiketmu masih tersimpan.</h2>
                            <p class="mb-0 text-white-50">
                                Masukkan nomor pesanan dan email pembelian. Kami kirim tautan untuk membuka detail pesanan dan e-tiket.
                            </p>
                        </div>
                        <div class="col-lg-4">
                            <div class="cta-actions">
                                <a class="btn btn-light" href="{{ route('guest.orders.lookup') }}">
                                    <i class="fa-solid fa-ticket me-1"></i> Buka e-tiket saya
                                </a>
                                <a class="btn btn-outline-light" href="{{ route('info.refund') }}">Kebijakan refund</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('styles')
        <style>
            .home-page {
                --section-y: 2.75rem;
            }

            .home-page .home-hero {
                padding: calc(4.6rem + env(safe-area-inset-top)) 0 2.5rem;
            }

            .home-page .hero-title {
                font-size: clamp(1.65rem, 6.4vw, 3rem);
                line-height: 1.08;
                letter-spacing: -.03em;
                text-wrap: balance;
            }

            .home-page .hero-subtitle {
                font-size: 1rem;
                line-height: 1.55;
            }

            .home-page .section {
                padding: var(--section-y) 0;
            }

            .home-page .section-categories {
                padding-bottom: 0;
            }

            .home-page .section-head-row {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: 1.25rem;
            }

            .home-page .section-title {
                font-size: clamp(1.45rem, 4.5vw, 2.5rem);
                line-height: 1.12;
                letter-spacing: -.025em;
            }

            .home-page .section-head p {
                font-size: .95rem;
            }

            .home-page .section-more {
                align-self: flex-start;
                padding-block: .35rem;
            }

            .home-page .search-card {
                background: var(--paper);
                border: 1px solid var(--line);
                box-shadow: none;
            }

            .home-page .search-card .form-label {
                margin-bottom: .3rem;
                font-size: .8rem;
            }

            .home-page .search-card .form-control,
            .home-page .search-card .form-select {
                min-height: 2.75rem;
                border-color: var(--line);
                padding: .6rem .8rem;
                font-size: 1rem;
            }

            .home-page .search-submit {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 3.5rem;
                padding-inline: 1rem;
            }

            .home-page .btn-filter {
                min-height: 2.75rem;
                border: 1px dashed var(--line);
                background: transparent;
                color: var(--ink);
                font-weight: 600;
            }

            .home-page .btn-filter:hover,
            .home-page .btn-filter:focus {
                border-color: var(--teal);
                color: var(--teal);
            }

            .home-page .hero-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: .15rem 1rem;
            }

            .home-page .hero-meta .text-link {
                padding-block: .35rem;
            }

            .home-page .hero-note {
                flex: 1 1 100%;
                font-size: .8rem;
                line-height: 1.4;
            }

            .home-page .hero-event-card {
                display: grid;
                grid-template-columns: 36% minmax(0, 1fr);
                overflow: hidden;
            }

            .home-page .hero-event-poster {
                position: relative;
                height: 100%;
                min-height: 9rem;
                overflow: hidden;
                background: var(--mint);
            }

            .home-page .hero-event-poster img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform .35s ease;
            }

            .home-page .hero-event-card:hover .hero-event-poster img {
                transform: scale(1.03);
            }

            .home-page .hero-event-poster .event-badge {
                display: none;
            }

            .home-page .hero-event-body {
                display: flex;
                flex-direction: column;
                min-width: 0;
                padding: .9rem;
            }

            .home-page .hero-event-title {
                font-size: 1rem;
                line-height: 1.25;
            }

            .home-page .hero-event-title a {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .home-page .hero-event-meta {
                display: flex;
                flex-direction: column;
                gap: .15rem;
                font-size: .78rem;
            }

            .home-page .hero-event-meta i {
                width: 1rem;
                text-align: center;
            }

            .home-page .hero-event-footer {
                display: flex;
                flex-direction: column;
                gap: .5rem;
                margin-top: auto;
                padding-top: .5rem;
            }

            .home-page .hero-event-cta {
                width: 100%;
            }

            .category-scroller {
                display: flex;
                gap: .75rem;
                overflow-x: auto;
                padding-bottom: .35rem;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }

            .category-scroller::-webkit-scrollbar {
                display: none;
            }

            .category-scroller > .category-card {
                flex: 0 0 9.75rem;
                padding: .9rem;
                color: var(--ink);
                text-decoration: none;
                scroll-snap-align: start;
            }

            .category-scroller > .category-card > div {
                min-width: 0;
            }

            .category-card {
                transition: border-color .2s ease;
            }

            .category-card:hover {
                border-color: var(--teal);
            }

            .category-icon {
                width: 2.5rem;
                height: 2.5rem;
                background: var(--mint);
                color: var(--teal);
                display: grid;
                place-items: center;
                font-size: 1rem;
                flex-shrink: 0;
            }

            .category-label {
                font-weight: 600;
                font-size: .92rem;
                line-height: 1.2;
            }

            .category-count {
                font-size: .75rem;
            }

            .event-scroller {
                display: flex;
                gap: .9rem;
                overflow-x: auto;
                padding-bottom: .5rem;
                scroll-snap-type: x mandatory;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }

            .event-scroller::-webkit-scrollbar {
                display: none;
            }

            .event-scroller-item {
                display: flex;
                flex: 0 0 min(82vw, 20rem);
                scroll-snap-align: start;
            }

            .event-scroller-item > * {
                width: 100%;
            }

            .event-poster {
                position: relative;
                aspect-ratio: 16 / 10;
                overflow: hidden;
                background: var(--mint);
            }

            .event-poster img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform .35s ease;
            }

            .event-card:hover .event-poster img {
                transform: scale(1.04);
            }

            .event-badge {
                position: absolute;
                top: .85rem;
                right: .85rem;
                background: var(--teal);
                color: #fff;
                padding: .3rem .75rem;
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
            }

            .event-badge.badge-sold {
                background: #6c757d;
            }

            .event-card-compact-link {
                display: grid;
                grid-template-columns: 6.75rem minmax(0, 1fr);
                height: 100%;
                color: var(--ink);
                text-decoration: none;
            }

            .event-poster-compact {
                position: relative;
                height: 100%;
                min-height: 8.5rem;
                aspect-ratio: auto;
                overflow: hidden;
                background: var(--mint);
            }

            .event-poster-compact img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform .35s ease;
            }

            .event-card-compact:hover .event-poster-compact img {
                transform: scale(1.04);
            }

            .event-poster-compact .event-badge {
                top: .4rem;
                right: .4rem;
                padding: .2rem .45rem;
                font-size: .58rem;
                letter-spacing: .03em;
            }

            .event-card-compact-body {
                display: flex;
                flex-direction: column;
                min-width: 0;
                padding: .85rem;
            }

            .event-card-compact-title {
                display: -webkit-box;
                overflow: hidden;
                font-size: .98rem;
                line-height: 1.3;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .event-card-compact-footer {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: .5rem;
                margin-top: auto;
                padding-top: .4rem;
            }

            .event-card-compact-cta {
                color: var(--teal);
                font-size: .82rem;
                font-weight: 600;
                white-space: nowrap;
            }

            .step-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: .75rem;
            }

            .step-card {
                padding: 1rem;
            }

            .step-card .icon-box {
                width: 2.5rem;
                height: 2.5rem;
                font-size: 1rem;
            }

            .step-card .step-number {
                font-size: 1.4rem;
            }

            .step-card h3 {
                font-size: 1rem;
            }

            .step-card p {
                font-size: .85rem;
            }

            .benefit-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: .75rem;
            }

            .benefit-card {
                display: grid;
                grid-template-columns: 2.5rem minmax(0, 1fr);
                align-items: start;
                column-gap: .75rem;
                padding: .9rem 1rem;
            }

            .benefit-card .icon-box {
                grid-column: 1;
                grid-row: 1 / span 2;
                width: 2.5rem;
                height: 2.5rem;
                margin-bottom: 0;
                font-size: 1rem;
            }

            .benefit-card h3,
            .benefit-card p {
                grid-column: 2;
                min-width: 0;
            }

            .benefit-card h3 {
                font-size: .95rem;
                margin-bottom: .15rem;
            }

            .benefit-card p {
                font-size: .83rem;
            }

            .organizer-actions {
                display: flex;
                flex-direction: column;
                gap: .5rem;
            }

            .home-page .cta-band {
                padding: 1.25rem;
            }

            .home-page .cta-band .eyebrow {
                color: #a7e8e2;
            }

            .home-page .cta-title {
                font-size: clamp(1.4rem, 4vw, 2.25rem);
            }

            .home-page .cta-actions {
                display: flex;
                flex-direction: column;
                gap: .5rem;
            }

            .home-page .cta-actions .btn {
                min-height: 2.75rem;
            }

            @media (min-width: 360px) {
                .step-grid.step-grid-4 {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 576px) {
                .home-page .hero-event-card {
                    grid-template-columns: 40% minmax(0, 1fr);
                }

                .home-page .hero-event-poster .event-badge {
                    display: inline-block;
                }

                .home-page .hero-event-body {
                    padding: 1rem;
                }

                .home-page .hero-event-title {
                    font-size: 1.1rem;
                }

                .home-page .hero-event-footer {
                    flex-direction: row;
                    align-items: flex-end;
                    justify-content: space-between;
                }

                .home-page .hero-event-cta {
                    width: auto;
                }

                .benefit-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .benefit-card {
                    display: block;
                    padding: 1rem;
                }

                .benefit-card .icon-box {
                    grid-column: auto;
                    grid-row: auto;
                    margin-bottom: .75rem;
                }

                .benefit-card h3,
                .benefit-card p {
                    grid-column: auto;
                }

                .category-scroller {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: .75rem;
                    overflow: visible;
                    padding-bottom: 0;
                }

                .category-scroller > .category-card {
                    flex: none;
                }

                .organizer-actions {
                    flex-direction: row;
                    flex-wrap: wrap;
                }

                .home-page .cta-actions {
                    flex-direction: row;
                    flex-wrap: wrap;
                }
            }

            @media (min-width: 768px) {
                .home-page {
                    --section-y: 4rem;
                }

                .home-page .home-hero {
                    padding: 6rem 0 3.5rem;
                }

                .home-page .hero-event-card {
                    grid-template-columns: 42% minmax(0, 1fr);
                }

                .home-page .hero-event-body {
                    padding: 1.25rem;
                }

                .home-page .section-head-row {
                    flex-direction: row;
                    align-items: flex-end;
                    justify-content: space-between;
                    margin-bottom: 1.75rem;
                }

                .home-page .section-more {
                    align-self: auto;
                }

                .event-scroller {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 1rem;
                    overflow: visible;
                    padding-bottom: 0;
                }

                .event-scroller-item {
                    flex: none;
                }

                .event-card-compact-link {
                    grid-template-columns: 7.5rem minmax(0, 1fr);
                }

                .event-card-compact-title {
                    font-size: 1.02rem;
                }

                .step-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    gap: 1rem;
                }

                .step-grid.step-grid-4 {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .step-card {
                    padding: 1.25rem;
                }

                .benefit-card {
                    padding: 1.25rem;
                }

                .home-page .cta-band {
                    padding: 2rem;
                }

                .home-page .cta-actions {
                    flex-direction: row;
                    justify-content: flex-end;
                }
            }

            @media (min-width: 992px) {
                .home-page .home-hero {
                    padding: 8.5rem 0 4.5rem;
                }

                .home-page .hero-subtitle {
                    font-size: 1.125rem;
                }

                .home-page .hero-event-card {
                    display: block;
                }

                .home-page .hero-event-poster {
                    height: auto;
                    min-height: 0;
                    aspect-ratio: 16 / 10;
                }

                .home-page .hero-event-poster .event-badge {
                    display: inline-block;
                    top: .85rem;
                    right: .85rem;
                    padding: .3rem .75rem;
                    font-size: .68rem;
                    letter-spacing: .04em;
                }

                .home-page .hero-event-body {
                    padding: 1.5rem;
                }

                .home-page .hero-event-title {
                    font-size: 1.3rem;
                }

                .home-page .hero-event-title a {
                    -webkit-line-clamp: 3;
                }

                .category-scroller {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }

                .event-scroller {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                .home-page .cta-title {
                    font-size: 2.25rem;
                }

                .home-page .cta-actions {
                    flex-direction: column;
                    align-items: stretch;
                    justify-content: flex-end;
                }
            }

            @media (min-width: 1200px) {
                .home-page {
                    --section-y: 5rem;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .home-page .hero-event-card:hover .hero-event-poster img,
                .event-card:hover .event-poster img,
                .event-card-compact:hover .event-poster-compact img {
                    transform: none;
                }
            }
        </style>
    @endpush
</x-layouts.public>
