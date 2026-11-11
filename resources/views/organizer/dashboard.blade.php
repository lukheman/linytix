<x-layouts.app title="Dashboard Organizer - LinyTix" brand-name="LinyTix" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Dashboard Organizer" subtitle="Ringkasan profil dan event milik organizer Anda.">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('organizer.profile') }}">
                <i class="fas fa-building me-2"></i>Profil Organizer
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    @if (! $organizer->is_verified)
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>Organizer belum terverifikasi.</strong>
                Anda dapat menyiapkan event draft, tetapi belum dapat submit review atau publish sampai admin memverifikasi profil.
            </div>
            <a href="{{ route('organizer.profile') }}" class="btn btn-sm btn-outline-dark">Lengkapi profil</a>
        </div>
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
                    <p class="text-muted mb-0">Belum ada event. Pengelolaan event akan tersedia pada tahap berikutnya.</p>
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
                                            <div class="fw-semibold">{{ $event->title }}</div>
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
                    @else
                        <x-ui.badge variant="warning">Belum terverifikasi</x-ui.badge>
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
