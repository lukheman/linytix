@props([
    'title' => 'Akun — LinyTix',
    'description' => 'Masuk atau daftar akun LinyTix untuk membeli tiket dan mengelola event.',
    'eyebrow' => 'Akun',
    'heading' => 'Selamat datang.',
    'subheading' => null,
])

@php
    $settings = \App\Support\SiteContent::settings();

    $benefits = [
        ['icon' => 'fa-ticket', 'title' => 'E-tiket ber-QR', 'text' => 'Tiket langsung tersedia setelah pembayaran dan dapat dipindai di pintu masuk.'],
        ['icon' => 'fa-shield-halved', 'title' => 'Pembayaran aman', 'text' => 'Transaksi diproses melalui payment gateway resmi dengan status yang transparan.'],
        ['icon' => 'fa-calendar-plus', 'title' => 'Untuk organizer', 'text' => 'Ajukan profil organizer dan daftarkan event Anda dari satu dashboard.'],
    ];
@endphp

<x-layouts.public
    :settings="$settings"
    :title="$title"
    :description="$description"
>
    @push('styles')
        <style>
            .auth-shell {
                padding: 8.5rem 0 5.5rem;
            }

            .auth-grid {
                display: grid;
                gap: 2.5rem;
            }

            @media (min-width: 992px) {
                .auth-grid {
                    grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr);
                    gap: 4.5rem;
                    align-items: start;
                }
            }

            .auth-aside {
                max-width: 34rem;
            }

            .auth-benefit {
                display: flex;
                gap: 1rem;
                align-items: flex-start;
            }

            .auth-benefit-icon {
                width: 2.6rem;
                height: 2.6rem;
                flex: 0 0 auto;
                display: grid;
                place-items: center;
                background: var(--mint);
                color: var(--teal);
            }

            .auth-card {
                background: var(--paper);
                border: 1px solid var(--line);
                padding: 1.75rem;
            }

            @media (min-width: 768px) {
                .auth-card {
                    padding: 2.5rem;
                }
            }

            .auth-label {
                display: block;
                margin-bottom: .4rem;
                color: var(--muted);
                font-size: .78rem;
                font-weight: 700;
                letter-spacing: .06em;
                text-transform: uppercase;
            }

            .auth-input {
                width: 100%;
                padding: .78rem .9rem;
                border: 1px solid var(--line);
                background: #fff;
                color: var(--ink);
                font-size: .95rem;
            }

            .auth-input:focus {
                border-color: var(--teal);
                outline: 0;
                box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
            }

            .auth-input.is-invalid {
                border-color: #b42318;
            }

            .auth-password {
                position: relative;
            }

            .auth-password .auth-input {
                padding-right: 3rem;
            }

            .auth-toggle {
                position: absolute;
                top: 50%;
                right: .25rem;
                transform: translateY(-50%);
                display: grid;
                place-items: center;
                width: 2.5rem;
                height: 2.5rem;
                border: 0;
                background: transparent;
                color: var(--muted);
            }

            .auth-toggle:hover,
            .auth-toggle:focus-visible {
                color: var(--teal);
            }

            .auth-divider {
                display: flex;
                align-items: center;
                gap: .85rem;
                margin: 1.5rem 0;
                color: var(--muted);
                font-size: .78rem;
                font-weight: 600;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .auth-divider::before,
            .auth-divider::after {
                content: "";
                flex: 1;
                height: 1px;
                background: var(--line);
            }

            .auth-note {
                color: var(--muted);
                font-size: .875rem;
            }

            .auth-card .form-check-input {
                border-color: var(--line);
            }

            .auth-card .form-check-input:checked {
                background-color: var(--teal);
                border-color: var(--teal);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function togglePassword(inputId, iconId) {
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);

                if (!input || !icon) {
                    return;
                }

                const show = input.type === 'password';
                const button = icon.closest('button');

                input.type = show ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);

                if (button) {
                    button.setAttribute('aria-pressed', show ? 'true' : 'false');
                    button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                }
            }
        </script>
    @endpush

    <section class="auth-shell">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <a href="{{ route('home') }}" class="text-link small">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke beranda
                </a>
            </nav>

            <div class="auth-grid">
                <aside class="auth-aside">
                    <span class="eyebrow">{{ $eyebrow }}</span>
                    <h1 class="display-5 mt-2 mb-3">{{ $heading }}</h1>
                    @if ($subheading)
                        <p class="lead text-secondary mb-4">{{ $subheading }}</p>
                    @endif

                    @isset($aside)
                        {{ $aside }}
                    @else
                        <div class="d-flex flex-column gap-4 mt-4">
                            @foreach ($benefits as $benefit)
                                <div class="auth-benefit">
                                    <div class="auth-benefit-icon">
                                        <i class="fa-solid {{ $benefit['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $benefit['title'] }}</div>
                                        <p class="text-secondary small mb-0">{{ $benefit['text'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="auth-note mt-4 mb-0">
                            Butuh bantuan? <a href="{{ route('faq') }}" class="text-link">Baca FAQ</a> atau
                            <a href="{{ route('organizer.guide') }}" class="text-link">panduan organizer</a>.
                        </p>
                    @endisset
                </aside>

                <div class="auth-card">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
