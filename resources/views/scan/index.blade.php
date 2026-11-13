<x-layouts.app title="Scan Tiket - Nusa Event" brand-name="Nusa Event" brand-icon="fas fa-qrcode">
    <x-layout.page-header title="Scan Tiket" subtitle="Pilih event yang ingin discan." />

    @if ($isAdmin)
        <div class="alert alert-info small">
            Sebagai admin Anda dapat membuka halaman scan semua event yang sedang tayang.
        </div>
    @endif

    <div class="row g-4">
        @forelse ($events as $event)
            <div class="col-md-6 col-xl-4">
                <div class="modern-card h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="mb-0">{{ $event->title }}</h5>
                        <x-events.status-badge :status="$event->status" />
                    </div>

                    <div class="small text-muted mb-1">
                        <i class="fas fa-calendar-days me-1"></i>
                        {{ $event->startsAtLocal()?->translatedFormat('d M Y, H.i') ?? 'Jadwal menyusul' }}
                    </div>
                    <div class="small text-muted mb-3">
                        <i class="fas fa-location-dot me-1"></i>
                        {{ $event->is_online ? 'Online' : ($event->venue?->name ?? 'Venue menyusul') }}
                    </div>

                    @unless ($isAdmin)
                        <div class="mb-3">
                            <span class="badge text-bg-info">
                                <i class="fas fa-door-open me-1"></i>{{ $event->pivot->gate ?: 'Gate belum ditentukan' }}
                            </span>
                        </div>
                    @endunless

                    <a href="{{ route('scan.show', $event) }}" class="btn btn-primary mt-auto">
                        <i class="fas fa-qrcode me-1"></i>Buka Scan
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="modern-card text-center py-5">
                    <i class="fas fa-calendar-xmark fa-2x text-muted mb-3"></i>
                    <p class="text-muted mb-0">
                        {{ $isAdmin ? 'Belum ada event yang sedang tayang.' : 'Anda belum ditugaskan sebagai staff pada event mana pun.' }}
                    </p>
                </div>
            </div>
        @endforelse
    </div>
</x-layouts.app>
