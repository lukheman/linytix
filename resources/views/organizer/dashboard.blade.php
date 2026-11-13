<x-layouts.app title="Dashboard Organizer - LinyTix" brand-name="LinyTix" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Dashboard Organizer" subtitle="Ringkasan profil dan event milik organizer Anda.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('organizer.events.create') }}">
                <i class="fas fa-plus me-2"></i>Buat Event
            </a>
            <a class="btn btn-outline-primary" href="{{ route('organizer.profile') }}">
                <i class="fas fa-building me-2"></i>Profil Organizer
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    @if (! $organizer->is_verified)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                @if ($organizer->verification_note)
                    <strong>Profil organizer perlu diperbaiki.</strong>
                    {{ $organizer->verification_note }}
                @else
                    <strong>Menunggu verifikasi admin.</strong>
                    Anda dapat menyiapkan event draft, tetapi belum dapat submit review atau publish sampai admin memverifikasi profil.
                @endif
            </div>
            <a href="{{ route('organizer.profile') }}" class="btn btn-sm btn-outline-dark">Lengkapi profil</a>
        </div>
    @endif

    @if (! $organizer->is_verified)
        <x-layout.modern-card title="Langkah menjadi organizer" class="mb-4">
            <p class="text-secondary small mb-3">
                @if ($organizer->verification_note)
                    Perbaiki profil sesuai catatan admin, lalu tunggu peninjauan ulang.
                @else
                    Pengajuan Anda sedang ditinjau admin. Sambil menunggu, Anda bisa menyiapkan draft event.
                @endif
            </p>
            <div class="row g-3">
                <div class="col-md-6">
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex gap-2 py-1"><i class="fas fa-circle-check text-success mt-1"></i><span>Pengajuan organizer terkirim</span></li>
                        <li class="d-flex gap-2 py-1"><i class="fas fa-circle-check text-success mt-1"></i><span>Email terverifikasi</span></li>
                        <li class="d-flex gap-2 py-1"><i class="fas fa-hourglass-half text-warning mt-1"></i><span>Menunggu verifikasi admin</span></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled small text-secondary mb-0">
                        <li class="d-flex gap-2 py-1"><i class="far fa-circle mt-1"></i><span>Lengkapi profil &amp; rekening</span></li>
                        <li class="d-flex gap-2 py-1"><i class="far fa-circle mt-1"></i><span>Buat event pertama</span></li>
                        <li class="d-flex gap-2 py-1"><i class="far fa-circle mt-1"></i><span>Tambah tiket &amp; ajukan review</span></li>
                    </ul>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('organizer.profile') }}" class="btn btn-sm btn-main">Lengkapi profil</a>
                <a href="{{ route('organizer.guide') }}" class="btn btn-sm btn-ghost">Baca panduan</a>
            </div>
        </x-layout.modern-card>
    @elseif ($counts->sum() === 0)
        <x-layout.modern-card title="Mulai daftarkan event pertama" class="mb-4">
            <p class="text-secondary small mb-3">
                Profil organizer Anda sudah terverifikasi. Ikuti langkah berikut untuk mendaftarkan event.
            </p>
            <div class="row g-3">
                @foreach ([
                    ['number' => '01', 'text' => 'Buat event draft: judul, kategori, jadwal, venue/online, deskripsi, dan poster.'],
                    ['number' => '02', 'text' => 'Tambahkan minimal satu jenis tiket aktif beserta harga dan kuota.'],
                    ['number' => '03', 'text' => 'Ajukan review admin. Setelah disetujui, event tayang di katalog.'],
                ] as $step)
                    <div class="col-md-4">
                        <div class="small">
                            <span class="step-number me-2">{{ $step['number'] }}</span>
                            {{ $step['text'] }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('organizer.events.create') }}" class="btn btn-sm btn-main">Buat Event</a>
                <a href="{{ route('organizer.guide') }}" class="btn btn-sm btn-ghost">Baca panduan</a>
            </div>
        </x-layout.modern-card>
    @endif

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Total Event', 'value' => $counts->sum(), 'icon' => 'fas fa-calendar-days', 'variant' => 'primary'],
                ['label' => 'Published', 'value' => $publishedCount, 'icon' => 'fas fa-circle-check', 'variant' => 'success'],
                ['label' => 'Draft', 'value' => (int) ($counts['draft'] ?? 0), 'icon' => 'fas fa-pen-to-square', 'variant' => 'warning'],
                ['label' => 'Menunggu Review', 'value' => (int) ($counts['pending_review'] ?? 0), 'icon' => 'fas fa-hourglass-half', 'variant' => 'info'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="col-6 col-lg-3">
                <div class="modern-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h3 mb-0 fw-bold">{{ $card['value'] }}</div>
                        </div>
                        <x-ui.badge :variant="$card['variant']"><i class="{{ $card['icon'] }}"></i></x-ui.badge>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-layout.modern-card title="Event Terbaru">
                @if ($events->isEmpty())
                    <p class="text-muted mb-0">Belum ada event. <a href="{{ route('organizer.events.create') }}">Buat event pertama Anda</a>.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($events as $event)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                <a href="{{ route('organizer.events.edit', $event) }}" class="text-decoration-none">{{ $event->title }}</a>
                                            </div>
                                            <small class="text-muted">{{ $event->venue?->city ?? ($event->is_online ? 'Online' : 'Lokasi menyusul') }}</small>
                                        </td>
                                        <td class="small text-secondary">{{ $event->start_at?->translatedFormat('d M Y, H.i') ?? '—' }}</td>
                                        <td><x-order-status :status="$event->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-layout.modern-card>
        </div>

        <div class="col-lg-5">
            <x-layout.modern-card title="Profil Organizer">
                <div class="fw-semibold fs-5">{{ $organizer->name }}</div>
                <div class="small text-muted mb-3">{{ $organizer->slug }}</div>

                <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                    <span class="text-secondary">Status verifikasi</span>
                    @if ($organizer->is_verified)
                        <x-ui.badge variant="success">Terverifikasi</x-ui.badge>
                    @elseif ($organizer->verification_note)
                        <x-ui.badge variant="danger">Perlu perbaikan</x-ui.badge>
                    @else
                        <x-ui.badge variant="warning">Menunggu verifikasi</x-ui.badge>
                    @endif
                </div>
                <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                    <span class="text-secondary">Email kontak</span>
                    <span class="small">{{ $organizer->contact_email ?? '—' }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                    <span class="text-secondary">Telepon</span>
                    <span class="small">{{ $organizer->contact_phone ?? '—' }}</span>
                </div>

                @if ($organizer->verification_note)
                    <div class="alert alert-info small mt-3 mb-0">
                        <strong>Catatan admin:</strong> {{ $organizer->verification_note }}
                    </div>
                @endif

                <a href="{{ route('organizer.profile') }}" class="btn btn-main w-100 mt-3">Kelola profil & rekening</a>
            </x-layout.modern-card>
        </div>
    </div>
</x-layouts.app>
