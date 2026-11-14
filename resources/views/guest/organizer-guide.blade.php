@php
    $siteName = $settings['site_name'] ?? 'LinyTix';

    $steps = [
        [
            'icon' => 'fa-user-plus',
            'title' => 'Daftar akun & verifikasi email',
            'text' => 'Buat akun customer, lalu verifikasi email melalui tautan yang kami kirim. Email terverifikasi diperlukan sebelum mengajukan organizer.',
        ],
        [
            'icon' => 'fa-building',
            'title' => 'Ajukan profil organizer',
            'text' => 'Buka halaman Daftar Organizer, isi nama, deskripsi, email, dan telepon kontak. Rekening pencairan boleh dilengkapi nanti.',
        ],
        [
            'icon' => 'fa-shield-halved',
            'title' => 'Tunggu verifikasi admin',
            'text' => 'Admin meninjau pengajuan Anda. Jika ada data yang kurang, admin mengirim catatan perbaikan yang tampil di dashboard.',
        ],
        [
            'icon' => 'fa-calendar-plus',
            'title' => 'Buat event pertama',
            'text' => 'Setelah terverifikasi, buat event draft: judul, kategori, venue/online, jadwal, timezone, deskripsi, dan poster.',
        ],
        [
            'icon' => 'fa-ticket',
            'title' => 'Tambahkan jenis tiket',
            'text' => 'Minimal satu jenis tiket aktif dengan harga dan kuota. Pastikan total kuota tidak melebihi kapasitas venue.',
        ],
        [
            'icon' => 'fa-paper-plane',
            'title' => 'Ajukan review & tayang',
            'text' => 'Ajukan event untuk review admin. Setelah disetujui, event tayang di katalog dan bisa mulai dijual.',
        ],
    ];
@endphp

<x-layouts.public
    :settings="$settings"
    :title="$seo['title']"
    :description="$seo['description']"
    :canonical="route('organizer.guide')"
>
    @push('styles')
        <style>
            .guide-aside-card {
                position: sticky;
                top: 6rem;
            }
        </style>
    @endpush

    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <a href="{{ route('home') }}" class="text-link small">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke beranda
                </a>
            </nav>

            <div class="row g-5">
                <div class="col-lg-7">
                    <span class="eyebrow">Panduan organizer</span>
                    <h1 class="display-5 mt-2 mb-3">Panduan mendaftarkan event.</h1>
                    <p class="lead text-secondary mb-4">
                        Ingin menjual tiket event di {{ $siteName }}? Ikuti langkah berikut untuk mendaftar sebagai
                        organizer dan mendaftarkan event pertama Anda. Anda bisa mendaftarkan event di sini setelah
                        profil organizer diverifikasi admin.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mb-5">
                        <a href="{{ \App\Support\OrganizerCta::url() }}" class="btn btn-main">
                            <i class="fas fa-building me-1"></i> Daftar sebagai Organizer
                        </a>
                        <a href="{{ route('faq') }}" class="btn btn-ghost">
                            <i class="fa-regular fa-circle-question me-1"></i> Baca FAQ
                        </a>
                    </div>

                    <div class="row g-3">
                        @foreach ($steps as $index => $step)
                            <div class="col-md-6">
                                <div class="card-clean p-4 h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="icon-box"><i class="fa-solid {{ $step['icon'] }}"></i></div>
                                        <div class="step-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                    <h2 class="h5">{{ $step['title'] }}</h2>
                                    <p class="text-secondary small mb-0">{{ $step['text'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="card-clean p-4 p-md-5 mt-4">
                        <h2 class="h4 mb-3">Yang perlu disiapkan saat membuat event</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <ul class="text-secondary small mb-0 ps-3">
                                    <li class="mb-2">Judul dan deskripsi event</li>
                                    <li class="mb-2">Kategori event</li>
                                    <li class="mb-2">Venue atau tautan event online</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="text-secondary small mb-0 ps-3">
                                    <li class="mb-2">Tanggal, jam, dan timezone</li>
                                    <li class="mb-2">Poster event</li>
                                    <li>Minimal satu jenis tiket aktif</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-clean p-4 p-md-5 mt-4">
                        <h2 class="h4 mb-3">Setelah event tayang</h2>
                        <div class="row g-3">
                            @foreach ([
                                ['icon' => 'fa-chart-line', 'title' => 'Pantau penjualan', 'text' => 'Lihat tiket terjual, pendapatan, dan rincian harian dari laporan penjualan.'],
                                ['icon' => 'fa-user-group', 'title' => 'Kelola staff & gate', 'text' => 'Tugaskan staff untuk memindai tiket di pintu masuk event.'],
                                ['icon' => 'fa-pen-to-square', 'title' => 'Perbarui informasi', 'text' => 'Deskripsi dan galeri masih dapat diubah. Field penjualan dikunci untuk menjaga konsistensi tiket.'],
                            ] as $item)
                                <div class="col-md-4">
                                    <div class="icon-box mb-2"><i class="fa-solid {{ $item['icon'] }}"></i></div>
                                    <h3 class="h6">{{ $item['title'] }}</h3>
                                    <p class="text-secondary small mb-0">{{ $item['text'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5">
                        <h2 class="h4 mb-3">Pertanyaan umum</h2>
                        <div class="d-flex flex-column gap-2">
                            @foreach ([
                                ['q' => 'Berapa lama proses verifikasi organizer?', 'a' => 'Admin meninjau pengajuan secara manual. Anda dapat memantau status di dashboard organizer.'],
                                ['q' => 'Apa yang terjadi jika pengajuan ditolak?', 'a' => 'Catatan admin tampil di dashboard dan profil organizer. Perbaiki data yang diminta, lalu tunggu peninjauan ulang.'],
                                ['q' => 'Kapan event bisa mulai dijual?', 'a' => 'Setelah event diajukan untuk review dan disetujui admin, event tayang di katalog dan tiket dapat dibeli.'],
                                ['q' => 'Apakah rekening wajib saat mendaftar?', 'a' => 'Tidak. Rekening pencairan bersifat opsional saat pengajuan dan dapat dilengkapi kemudian di profil organizer.'],
                            ] as $faq)
                                <div class="card-clean p-4">
                                    <h3 class="h6 mb-2">{{ $faq['q'] }}</h3>
                                    <p class="text-secondary small mb-0">{{ $faq['a'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card-clean p-4 p-md-5 guide-aside-card">
                        <span class="eyebrow">Siap mulai?</span>
                        <h2 class="h3 mt-2 mb-3">Daftarkan event Anda di {{ $siteName }}.</h2>
                        <p class="text-secondary small mb-4">
                            Gratis mendaftar. Anda hanya perlu akun dengan email terverifikasi, lalu lengkapi profil
                            organizer untuk mulai membuat event.
                        </p>

                        <ul class="list-unstyled small mb-4">
                            <li class="d-flex gap-2 mb-2">
                                <i class="fas fa-circle-check text-success mt-1"></i>
                                <span>Kelola event dan tiket dalam satu dashboard.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="fas fa-circle-check text-success mt-1"></i>
                                <span>Pantau penjualan dan kehadiran secara real-time.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="fas fa-circle-check text-success mt-1"></i>
                                <span>E-tiket ber-QR dan validasi scan di pintu masuk.</span>
                            </li>
                            <li class="d-flex gap-2">
                                <i class="fas fa-circle-check text-success mt-1"></i>
                                <span>Panduan dan status pengajuan yang transparan.</span>
                            </li>
                        </ul>

                        @auth
                            @if (auth()->user()->isOrganizer())
                                <a href="{{ route('organizer.dashboard') }}" class="btn btn-main w-100 mb-2">
                                    <i class="fas fa-gauge-high me-1"></i> Buka Dashboard Organizer
                                </a>
                                <a href="{{ route('organizer.events.create') }}" class="btn btn-ghost w-100">
                                    <i class="fas fa-plus me-1"></i> Buat Event
                                </a>
                            @else
                                <a href="{{ \App\Support\OrganizerCta::url() }}" class="btn btn-main w-100">
                                    <i class="fas fa-building me-1"></i> Daftar sebagai Organizer
                                </a>
                            @endif
                        @else
                            <a href="{{ \App\Support\OrganizerCta::url() }}" class="btn btn-main w-100">
                                <i class="fas fa-building me-1"></i> Daftar sebagai Organizer
                            </a>
                            <p class="small text-secondary mt-2 mb-0">
                                Anda akan diarahkan ke halaman daftar akun terlebih dahulu.
                            </p>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section pt-0">
        <div class="container">
            <div class="cta-band p-4 p-md-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <span class="eyebrow" style="color: #a7e8e2">Untuk organizer</span>
                        <h2 class="display-6 text-white mt-2 mb-2">Siap mendaftarkan event pertama?</h2>
                        <p class="mb-0 text-white-50">
                            Buat akun, ajukan profil organizer, dan mulai susun event Anda. Tim kami siap meninjau pengajuan.
                        </p>
                    </div>
                    <div class="col-lg-4 d-flex flex-column flex-sm-row flex-lg-column gap-2 justify-content-lg-end">
                        <a class="btn btn-light" href="{{ \App\Support\OrganizerCta::url() }}">
                            <i class="fas fa-building me-1"></i> Daftar Organizer
                        </a>
                        <a class="btn btn-outline-light" href="{{ route('events.index') }}">Lihat katalog event</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
