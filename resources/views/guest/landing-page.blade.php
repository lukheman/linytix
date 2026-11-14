@php
    $siteName = $settings['site_name'] ?? 'LinyTix';
    $heroEyebrow = $settings['hero_eyebrow'] ?? 'Platform tiket event';
    $heroTitle = $settings['hero_title'] ?? 'Temukan event yang bikin harimu berkesan.';
    $heroSubtitle = $settings['hero_subtitle'] ?? 'Konser, seminar, workshop, dan festival pilihan. Pesan tiket online, bayar aman, dan terima e-tiket QR langsung di email.';
    $heroNote = $settings['hero_note'] ?? 'Tiket asli, QR unik, dan akses pesanan kapan saja.';

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
    <section class="hero home-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow d-inline-block mb-3">{{ $heroEyebrow }}</span>
                    <h1 class="display-3 mb-3">{{ $heroTitle }}</h1>
                    <p class="lead text-secondary mb-4">{{ $heroSubtitle }}</p>

                    <form method="GET" action="{{ route('events.index') }}" class="search-card p-3 p-md-4 mb-4" role="search">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="home-q">Cari event</label>
                                <input type="search" class="form-control" id="home-q" name="q"
                                    placeholder="Nama event, artis, atau topik">
                            </div>
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
                            <div class="col-12 d-flex flex-column flex-sm-row gap-2 mt-3">
                                <button type="submit" class="btn btn-main flex-grow-1">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Cari event
                                </button>
                                <a href="{{ route('events.index') }}" class="btn btn-ghost">Semua event</a>
                            </div>
                        </div>
                    </form>

                    <p class="small text-secondary mb-4">
                        <i class="fa-solid fa-circle-check me-1" style="color: var(--teal)"></i>
                        {{ $heroNote }}
                    </p>

                    <div class="d-flex flex-wrap gap-4">
                        <div>
                            <div class="stat-value">{{ number_format($stats['events']) }}</div>
                            <div class="small text-secondary">Event aktif</div>
                        </div>
                        <div>
                            <div class="stat-value">{{ number_format($stats['cities']) }}</div>
                            <div class="small text-secondary">Kota</div>
                        </div>
                        <div>
                            <div class="stat-value">{{ number_format($stats['categories']) }}</div>
                            <div class="small text-secondary">Kategori</div>
                        </div>
                        <div>
                            <div class="stat-value">{{ number_format($stats['organizers']) }}</div>
                            <div class="small text-secondary">Organizer</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    @if ($heroEvent)
                        <div class="hero-event-card card-clean">
                            <a href="{{ route('events.show', $heroEvent->slug) }}" class="hero-event-poster d-block">
                                <img src="{{ $heroEvent->displayPosterUrl() }}" alt="Poster {{ $heroEvent->title }}" fetchpriority="high">
                                <span class="event-badge">Event unggulan</span>
                            </a>
                            <div class="p-4">
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    @if ($heroEvent->category)
                                        <span class="badge rounded-pill text-bg-light border">{{ $heroEvent->category->name }}</span>
                                    @endif
                                    @if ($heroEvent->is_online)
                                        <span class="badge rounded-pill text-bg-light border">
                                            <i class="fa-solid fa-wifi me-1"></i>Online
                                        </span>
                                    @endif
                                </div>
                                <h2 class="h4 mb-2">
                                    <a class="text-decoration-none text-reset" href="{{ route('events.show', $heroEvent->slug) }}">
                                        {{ $heroEvent->title }}
                                    </a>
                                </h2>
                                <div class="text-secondary small mb-1">
                                    <i class="fa-regular fa-calendar me-2"></i>
                                    {{ $heroEvent->startsAtLocal()?->translatedFormat('l, d F Y, H.i') }} WIB
                                </div>
                                <div class="text-secondary small mb-3">
                                    <i class="fa-solid fa-location-dot me-2"></i>
                                    {{ $heroEvent->is_online ? 'Online' : ($heroEvent->venue?->name ?? 'Lokasi menyusul') }}
                                </div>
                                <div class="d-flex justify-content-between align-items-end gap-2">
                                    <div>
                                        <div class="small text-secondary">Mulai dari</div>
                                        <div class="fw-bold fs-4">
                                            {{ $heroPrice > 0 ? 'Rp '.number_format($heroPrice, 0, ',', '.') : 'Gratis' }}
                                        </div>
                                    </div>
                                    <a href="{{ route('events.show', $heroEvent->slug) }}" class="btn btn-main">
                                        Lihat tiket <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card-clean p-5 text-center">
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
        <section class="section pt-4 pb-0">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
                    <div class="section-head">
                        <span class="eyebrow">Kategori</span>
                        <h2 class="display-6 mt-2 mb-2">Pilih suasana yang kamu cari.</h2>
                        <p class="text-secondary mb-0">Dari konser musik sampai workshop pengembangan diri.</p>
                    </div>
                    <a class="text-link text-nowrap" href="{{ route('events.index') }}">
                        Semua event <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="row g-3">
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
                        <div class="col-6 col-md-3">
                            <a href="{{ route('events.index', ['category' => $category->slug]) }}"
                                class="card-clean category-card p-3 p-md-4 d-flex align-items-center gap-3 text-decoration-none text-dark h-100">
                                <div class="category-icon"><i class="fa-solid {{ $categoryIcon }}"></i></div>
                                <div>
                                    <div class="fw-semibold">{{ $category->name }}</div>
                                    <div class="small text-secondary">{{ $category->events_count }} event</div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($highlighted->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
                    <div class="section-head">
                        <span class="eyebrow">Pilihan editor</span>
                        <h2 class="display-6 mt-2 mb-2">Event unggulan</h2>
                        <p class="text-secondary mb-0">Event yang paling ditunggu dan pilihan terbaik untuk akhir pekanmu.</p>
                    </div>
                    <a class="text-link text-nowrap" href="{{ route('events.index') }}">
                        Lihat semua event <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach ($highlighted as $event)
                        <div class="col-md-6 col-lg-4">
                            <x-events.card :event="$event" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section section-soft">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
                <div class="section-head">
                    <span class="eyebrow">Jadwal terdekat</span>
                    <h2 class="display-6 mt-2 mb-2">Event mendatang</h2>
                    <p class="text-secondary mb-0">Amankan tiket lebih awal sebelum kuota habis.</p>
                </div>
                <a class="text-link text-nowrap" href="{{ route('events.index') }}">
                    Jelajahi semua <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            @if ($upcomingEvents->isEmpty())
                <div class="card-clean p-5 text-center">
                    <div class="icon-box mx-auto mb-3"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <h3 class="h5">Belum ada event mendatang</h3>
                    <p class="text-secondary mb-3">Event baru akan segera hadir. Sementara itu, lihat halaman bantuan atau hubungi kami.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('faq') }}" class="btn btn-main">Buka FAQ</a>
                        <a href="{{ route('faq') }}" class="btn btn-ghost">Buka FAQ</a>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($upcomingEvents as $event)
                        <div class="col-md-6 col-lg-4">
                            <x-events.card :event="$event" />
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
                <h2 class="display-6 mt-2 mb-2">Tiga langkah, tiket langsung di tangan.</h2>
                <p class="text-secondary mb-0">Tanpa antre dan tanpa ribet. Semua proses bisa diselesaikan online.</p>
            </div>

            <div class="row g-3">
                @foreach ([
                    ['number' => '01', 'icon' => 'fa-magnifying-glass', 'title' => 'Pilih event', 'text' => 'Jelajahi event, cek jadwal, lalu pilih jenis tiket yang sesuai.'],
                    ['number' => '02', 'icon' => 'fa-shield-halved', 'title' => 'Bayar aman', 'text' => 'Selesaikan pembayaran melalui metode yang tersedia. Data transaksi terlindungi.'],
                    ['number' => '03', 'icon' => 'fa-qrcode', 'title' => 'Terima e-tiket', 'text' => 'E-tiket ber-QR dikirim ke email dan bisa dibuka lagi dari akun atau tautan aman.'],
                ] as $step)
                    <div class="col-md-4">
                        <div class="card-clean p-4 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="icon-box"><i class="fa-solid {{ $step['icon'] }}"></i></div>
                                <div class="step-number">{{ $step['number'] }}</div>
                            </div>
                            <h3 class="h5">{{ $step['title'] }}</h3>
                            <p class="text-secondary small mb-0">{{ $step['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-4">
                    <span class="eyebrow">Kenapa beli di sini</span>
                    <h2 class="display-6 mt-2">Pengalaman beli tiket yang tenang.</h2>
                    <p class="text-secondary">Dari pemilihan event sampai tiket diterima, semuanya dibuat sederhana dan aman.</p>
                    <a class="btn btn-main mt-2" href="{{ route('faq') }}">
                        <i class="fa-regular fa-circle-question me-1"></i> Baca bantuan
                    </a>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        @foreach ([
                            ['icon' => 'fa-lock', 'title' => 'Pembayaran aman', 'text' => 'Transaksi diproses melalui payment gateway dengan verifikasi webhook.'],
                            ['icon' => 'fa-qrcode', 'title' => 'E-tiket QR unik', 'text' => 'Setiap tiket punya kode QR unik yang divalidasi di lokasi event.'],
                            ['icon' => 'fa-envelope-circle-check', 'title' => 'Akses guest', 'text' => 'Beli tanpa akun tetap bisa membuka pesanan dan tiket lewat tautan aman di email.'],
                            ['icon' => 'fa-rotate-left', 'title' => 'Kebijakan jelas', 'text' => 'Syarat pembelian dan kebijakan refund tersedia terbuka sebelum membeli.'],
                        ] as $benefit)
                            <div class="col-sm-6">
                                <div class="card-clean p-4 h-100">
                                    <div class="icon-box mb-3"><i class="fa-solid {{ $benefit['icon'] }}"></i></div>
                                    <h3 class="h6">{{ $benefit['title'] }}</h3>
                                    <p class="text-secondary small mb-0">{{ $benefit['text'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="untuk-organizer">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5">
                    <span class="eyebrow">Untuk organizer</span>
                    <h2 class="display-6 mt-2 mb-3">Punya event? Daftarkan event Anda di sini.</h2>
                    <p class="text-secondary mb-4">
                        Daftar sebagai organizer, lengkapi profil, dan buat event pertama Anda. Setelah diverifikasi
                        admin, event dapat diajukan untuk review dan tayang di katalog {{ $siteName }}.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @if (auth()->check() && auth()->user()->isOrganizer())
                            <a href="{{ route('organizer.dashboard') }}" class="btn btn-main">
                                <i class="fas fa-gauge-high me-1"></i> Buka Dashboard Organizer
                            </a>
                        @else
                            <a href="{{ \App\Support\OrganizerCta::url() }}" class="btn btn-main">
                                <i class="fas fa-building me-1"></i> Daftar sebagai Organizer
                            </a>
                        @endif
                        <a href="{{ route('organizer.guide') }}" class="btn btn-ghost">Baca panduan lengkap</a>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        @foreach ([
                            ['number' => '01', 'icon' => 'fa-user-check', 'title' => 'Daftar & verifikasi', 'text' => 'Buat akun, verifikasi email, lalu ajukan profil organizer.'],
                            ['number' => '02', 'icon' => 'fa-shield-halved', 'title' => 'Verifikasi admin', 'text' => 'Admin meninjau profil dan mengirim catatan jika ada perbaikan.'],
                            ['number' => '03', 'icon' => 'fa-calendar-plus', 'title' => 'Buat event & tiket', 'text' => 'Lengkapi detail event, unggah poster, dan tambahkan jenis tiket.'],
                            ['number' => '04', 'icon' => 'fa-rocket', 'title' => 'Review & tayang', 'text' => 'Ajukan review. Setelah disetujui, event tayang dan tiket bisa dijual.'],
                        ] as $step)
                            <div class="col-sm-6">
                                <div class="card-clean p-4 h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="icon-box"><i class="fa-solid {{ $step['icon'] }}"></i></div>
                                        <div class="step-number">{{ $step['number'] }}</div>
                                    </div>
                                    <h3 class="h6">{{ $step['title'] }}</h3>
                                    <p class="text-secondary small mb-0">{{ $step['text'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cta-band p-4 p-md-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <span class="eyebrow" style="color: #a7e8e2">Akses guest</span>
                        <h2 class="display-6 text-white mt-2 mb-2">Sudah punya pesanan?</h2>
                        <p class="mb-0 text-white-50">
                            Masukkan nomor order dan email pembeli. Kami kirimkan tautan aman untuk membuka pesanan dan e-tiket.
                        </p>
                    </div>
                    <div class="col-lg-4 d-flex flex-column flex-sm-row flex-lg-column gap-2 justify-content-lg-end">
                        <a class="btn btn-light" href="{{ route('guest.orders.lookup') }}">
                            <i class="fa-solid fa-ticket me-1"></i> Cari pesanan
                        </a>
                        <a class="btn btn-outline-light" href="{{ route('info.refund') }}">Kebijakan refund</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <style>
            .home-hero {
                padding: 8.5rem 0 4.5rem;
            }

            .search-card {
                background: var(--paper);
                border: 1px solid var(--line);
                border-radius: 0;
                box-shadow: none;
            }

            .search-card .form-control,
            .search-card .form-select {
                border-color: var(--line);
                border-radius: 0;
                padding: .65rem .8rem;
            }

            .hero-event-card {
                border-radius: 0;
                overflow: hidden;
            }

            .hero-event-poster {
                position: relative;
                aspect-ratio: 16 / 10;
                overflow: hidden;
                background: var(--mint);
            }

            .hero-event-poster img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform .35s ease;
            }

            .hero-event-card:hover .hero-event-poster img {
                transform: scale(1.03);
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
                border-radius: 0;
                padding: .3rem .75rem;
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
            }

            .event-badge.badge-sold {
                background: #6c757d;
            }

            .category-card {
                transition: border-color .2s ease;
            }

            .category-card:hover {
                border-color: var(--teal);
            }

            .category-icon {
                width: 2.8rem;
                height: 2.8rem;
                border-radius: 0;
                background: var(--mint);
                color: var(--teal);
                display: grid;
                place-items: center;
                font-size: 1.1rem;
                flex-shrink: 0;
            }
        </style>
    @endpush
</x-layouts.public>
