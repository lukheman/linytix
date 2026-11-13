@props([
    'title' => 'LinyTix',
    'description' => 'Partner event organizer untuk kegiatan yang berkesan.',
    'settings' => [],
    'socialLinks' => [],
    'ogImage' => null,
    'ogType' => 'website',
    'canonical' => null,
    'jsonLd' => null,
])

@php
    $siteName = $settings['site_name'] ?? 'LinyTix';
    $waUrl = \App\Support\SiteContent::whatsappUrl($settings);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#0f766e">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    @if ($canonical)
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <title>{{ $title }}</title>
    @if ($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fontawesome-free-7.2.0-web/css/all.min.css') }}">

    <style>
        :root {
            --ink: #141614;
            --teal: #0f766e;
            --teal-dark: #0b5c56;
            --mint: #edefec;
            --gold: #0f766e;
            --line: #e2e3df;
            --canvas: #f7f7f5;
            --paper: #ffffff;
            --muted: #565b58;
        }

        *,
        *::before,
        *::after {
            border-radius: 0 !important;
        }

        .rounded-circle {
            border-radius: 0 !important;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 400;
            color: var(--ink);
            background: var(--canvas);
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, h6, .display-font {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            letter-spacing: -.025em;
            line-height: 1.15;
        }

        h4, h5, h6 {
            font-weight: 700;
        }

        .display-1, .display-2, .display-3, .display-4, .display-5, .display-6 {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1.08;
        }

        .lead {
            font-weight: 500;
        }

        .fw-semibold {
            font-weight: 600 !important;
        }

        .navbar {
            background: rgba(247, 247, 245, .92);
            backdrop-filter: blur(12px);
        }

        .brand {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--ink);
            text-decoration: none;
            letter-spacing: -.03em;
        }

        .brand i {
            color: var(--gold);
        }

        .navbar .nav-link {
            color: var(--muted) !important;
            font-weight: 600;
        }

        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: var(--teal) !important;
        }

        .btn-main {
            background: var(--teal);
            color: #fff;
            border: 1px solid var(--teal);
            border-radius: 0;
            padding: .7rem 1.3rem;
            font-weight: 600;
        }

        .btn-main:hover,
        .btn-main:focus {
            background: var(--teal-dark);
            border-color: var(--teal-dark);
            color: #fff;
        }

        .btn-ghost {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--line);
            border-radius: 0;
            padding: .7rem 1.3rem;
            font-weight: 600;
        }

        .btn-ghost:hover {
            border-color: var(--teal);
            color: var(--teal);
        }

        .hero {
            background: var(--canvas);
            padding: 8.5rem 0 5.5rem;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid var(--line);
        }

        .eyebrow {
            font-family: 'Archivo', sans-serif;
            color: var(--teal);
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            font-size: .72rem;
        }

        .card-clean {
            border: 1px solid var(--line);
            border-radius: 0;
            background: var(--paper);
            box-shadow: none;
            height: 100%;
            transition: border-color .2s ease;
        }

        a.card-clean:hover {
            border-color: var(--teal);
        }

        .icon-box {
            width: 3rem;
            height: 3rem;
            border-radius: 0;
            background: var(--mint);
            color: var(--teal);
            display: grid;
            place-items: center;
            font-size: 1.1rem;
        }

        .section {
            padding: 5.5rem 0;
        }

        .section-soft {
            background: var(--mint);
        }

        .section-head {
            max-width: 40rem;
        }

        .stat-value {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 1.9rem;
            line-height: 1;
            letter-spacing: -.03em;
            color: var(--teal);
        }

        .text-link {
            color: var(--teal);
            font-weight: 600;
            text-decoration: none;
        }

        .text-link:hover {
            color: var(--teal-dark);
        }

        .media-frame {
            border-radius: 0;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            background: var(--mint);
            display: grid;
            place-items: center;
            color: var(--teal);
            font-size: 2rem;
        }

        .media-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .whatsapp {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            background: #25d366;
            color: #fff;
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 0;
            display: grid;
            place-items: center;
            font-size: 1.45rem;
            z-index: 1040;
            box-shadow: 0 8px 20px rgba(37, 211, 102, .3);
        }

        .whatsapp:hover {
            color: #fff;
            background: #1ebe5b;
        }

        .footer {
            background: var(--ink);
            color: #d5d8d5;
            padding: 3.5rem 0 2rem;
        }

        .footer a {
            color: #d5d8d5;
            text-decoration: none;
        }

        .footer a:hover {
            color: #fff;
        }

        .footer-social a {
            width: 2.4rem;
            height: 2.4rem;
            border: 1px solid rgba(213, 216, 213, .35);
            border-radius: 0;
            display: inline-grid;
            place-items: center;
        }

        .footer-bottom {
            border-top: 1px solid rgba(213, 216, 213, .18);
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: var(--canvas);
                border: 1px solid var(--line);
                border-radius: 0;
                margin-top: .75rem;
                padding: 1rem;
            }
        }

        .btn-primary {
            --bs-btn-bg: var(--teal);
            --bs-btn-border-color: var(--teal);
            --bs-btn-hover-bg: var(--teal-dark);
            --bs-btn-hover-border-color: var(--teal-dark);
            --bs-btn-active-bg: var(--teal-dark);
            --bs-btn-active-border-color: var(--teal-dark);
        }

        .text-primary { color: var(--teal) !important; }
        .bg-primary { background-color: var(--teal) !important; }
        .border-primary { border-color: var(--teal) !important; }
    </style>
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg py-3 fixed-top border-bottom">
    <div class="container">
        <a class="brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <i class="fa-solid fa-sparkles"></i>
            <span>{{ $siteName }}</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Buka menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div id="siteNav" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}" href="{{ route('events.index') }}">Event</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#cara-beli">Cara Beli</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('faq') ? 'active' : '' }}" href="{{ route('faq') }}">Bantuan</a></li>
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('akun.dashboard') }}">Akun saya</a></li>
                            <li><a class="dropdown-item" href="{{ route('akun.orders.index') }}">Pesanan</a></li>
                            <li><a class="dropdown-item" href="{{ route('akun.tickets.index') }}">Tiket saya</a></li>
                            @unless (auth()->user()->isOrganizer())
                                <li><a class="dropdown-item" href="{{ route('organizer.register') }}">Daftar Organizer</a></li>
                            @endunless
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">Masuk</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('guest.orders.*') ? 'active' : '' }}" href="{{ route('guest.orders.lookup') }}">Cari Pesanan</a></li>
                @endauth
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-main" href="{{ route('events.index') }}">
                        <i class="fa-solid fa-ticket me-1"></i> Jelajahi Event
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

{{ $slot }}

<footer class="footer">
    <div class="container">
        <div class="row g-4 pb-4">
            <div class="col-lg-5">
                <div class="h4 display-font text-white mb-2">{{ $siteName }}</div>
                <p class="mb-3 text-white-50">{{ $settings['footer_tagline'] ?? 'Beli tiket event favoritmu dengan pembayaran aman dan e-tiket QR.' }}</p>
                <div class="footer-social d-flex gap-2">
                    @foreach ($socialLinks as $link)
                        <a href="{{ $link->url }}" target="_blank" rel="noopener" aria-label="{{ $link->platform }}" title="{{ $link->platform }}">
                            <i class="{{ $link->icon ?: 'fa-solid fa-link' }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="text-white fw-semibold mb-3">Jelajahi</div>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('events.index') }}">Jelajahi Event</a>
                    <a href="{{ route('home') }}#cara-beli">Cara Beli</a>
                    <a href="{{ route('faq') }}">FAQ</a>
                    <a href="{{ route('guest.orders.lookup') }}">Cari Pesanan</a>
                    <a href="{{ route('info.about') }}">Tentang Platform</a>
                    <a href="{{ \App\Support\OrganizerCta::url() }}">Daftar Organizer</a>
                    <a href="{{ route('organizer.guide') }}">Panduan Organizer</a>
                    <a href="{{ route('info.terms') }}">Syarat & Ketentuan</a>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="text-white fw-semibold mb-3">Hubungi Kami</div>
                <div class="d-flex flex-column gap-2">
                    <span><i class="fa-solid fa-location-dot me-2"></i>{{ $settings['address'] ?? 'Alamat kantor belum diatur.' }}</span>
                    <a href="mailto:{{ $settings['email'] ?? 'halo@LinyTix.id' }}"><i class="fa-solid fa-envelope me-2"></i>{{ $settings['email'] ?? 'halo@LinyTix.id' }}</a>
                    <span><i class="fa-regular fa-clock me-2"></i>{{ $settings['operating_hours'] ?? 'Senin–Sabtu, 08.00–17.00' }}</span>
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp me-2"></i>Konsultasi via WhatsApp</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom pt-3 d-flex flex-column flex-md-row justify-content-between gap-2 small text-white-50">
            <span>&copy; {{ date('Y') }} {{ $siteName }}. Seluruh hak cipta dilindungi.</span>
            <span>Dirancang untuk acara yang berkesan.</span>
        </div>
    </div>
</footer>

<a class="whatsapp" href="{{ $waUrl }}" target="_blank" rel="noopener" aria-label="Konsultasi via WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
</a>

<script src="{{ asset('assets/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
