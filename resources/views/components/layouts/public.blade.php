@props([
    'title' => 'Nusa Event Organizer',
    'description' => 'Partner event organizer untuk kegiatan yang berkesan.',
    'settings' => [],
    'socialLinks' => [],
    'ogImage' => null,
    'ogType' => 'website',
    'canonical' => null,
    'jsonLd' => null,
])

@php
    $siteName = $settings['site_name'] ?? 'Nusa Event';
    $waUrl = \App\Support\SiteContent::whatsappUrl($settings);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#0d6865">
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
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fontawesome-free-7.2.0-web/css/all.min.css') }}">

    <style>
        :root {
            --ink: #102a2b;
            --teal: #0d6865;
            --teal-dark: #084d4b;
            --mint: #e6f3ef;
            --gold: #d99b4b;
            --line: #dce9e5;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 400;
            color: var(--ink);
            background: #fbfcfa;
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
            background: rgba(251, 252, 250, .95);
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
            color: #385553 !important;
            font-weight: 600;
        }

        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: var(--teal) !important;
        }

        .btn-main {
            background: var(--teal);
            color: #fff;
            border: 2px solid var(--teal);
            border-radius: 999px;
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
            border: 2px solid var(--line);
            border-radius: 999px;
            padding: .7rem 1.3rem;
            font-weight: 600;
        }

        .btn-ghost:hover {
            border-color: var(--teal);
            color: var(--teal);
        }

        .hero {
            background: linear-gradient(120deg, var(--mint), #fbfcfa 65%);
            padding: 8.5rem 0 5.5rem;
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 28rem;
            height: 28rem;
            border-radius: 50%;
            right: -10rem;
            top: -12rem;
            background: radial-gradient(circle, rgba(217, 155, 75, .18), transparent 70%);
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
            border-radius: 1.1rem;
            background: #fff;
            box-shadow: 0 14px 35px rgba(22, 76, 75, .05);
            height: 100%;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        a.card-clean:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 42px rgba(22, 76, 75, .11);
        }

        .icon-box {
            width: 3.2rem;
            height: 3.2rem;
            border-radius: 1rem;
            background: var(--mint);
            color: var(--teal);
            display: grid;
            place-items: center;
            font-size: 1.15rem;
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
            border-radius: 1rem;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            background: linear-gradient(135deg, var(--mint), #d6ebe4);
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
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 1.55rem;
            z-index: 1040;
            box-shadow: 0 12px 25px rgba(37, 211, 102, .35);
        }

        .whatsapp:hover {
            color: #fff;
            background: #1ebe5b;
        }

        .footer {
            background: var(--ink);
            color: #d8e6e2;
            padding: 3.5rem 0 2rem;
        }

        .footer a {
            color: #d8e6e2;
            text-decoration: none;
        }

        .footer a:hover {
            color: #fff;
        }

        .footer-social a {
            width: 2.4rem;
            height: 2.4rem;
            border: 1px solid rgba(216, 230, 226, .35);
            border-radius: 50%;
            display: inline-grid;
            place-items: center;
        }

        .footer-bottom {
            border-top: 1px solid rgba(216, 230, 226, .18);
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: #fbfcfa;
                border: 1px solid var(--line);
                border-radius: 1rem;
                margin-top: .75rem;
                padding: 1rem;
            }
        }
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
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#katalog">Katalog Sewa</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">Tentang</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('services*') ? 'active' : '' }}" href="{{ route('services') }}">Layanan</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('collaborations*') ? 'active' : '' }}" href="{{ route('collaborations') }}">Kerja Sama</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('testimonials') ? 'active' : '' }}" href="{{ route('testimonials') }}">Testimoni</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Kontak</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-main" href="{{ $waUrl }}" target="_blank" rel="noopener">
                        <i class="fa-brands fa-whatsapp me-1"></i> Konsultasi
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
                <p class="mb-3 text-white-50">{{ $settings['footer_tagline'] ?? 'Sewa perlengkapan event dengan antar, pasang, dan penarikan.' }}</p>
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
                    <a href="{{ route('home') }}#katalog">Katalog Sewa</a>
                    <a href="{{ route('about') }}">Tentang Kami</a>
                    <a href="{{ route('faq') }}">FAQ</a>
                    <a href="{{ route('info.terms') }}">Syarat & Ketentuan</a>
                </div>
            </div>
            <div class="col-6 col-lg-4">
                <div class="text-white fw-semibold mb-3">Hubungi Kami</div>
                <div class="d-flex flex-column gap-2">
                    <span><i class="fa-solid fa-location-dot me-2"></i>{{ $settings['address'] ?? 'Alamat kantor belum diatur.' }}</span>
                    <a href="mailto:{{ $settings['email'] ?? 'halo@nusaevent.id' }}"><i class="fa-solid fa-envelope me-2"></i>{{ $settings['email'] ?? 'halo@nusaevent.id' }}</a>
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
