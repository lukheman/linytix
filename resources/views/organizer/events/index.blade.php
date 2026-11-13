<x-layouts.app title="Event Saya - Nusa Event" brand-name="Nusa Event" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Event Saya" subtitle="Kelola draft, ajukan review, dan pantau status event organizer Anda.">
        <x-slot:actions>
            <a href="{{ route('organizer.events.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Buat Event
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $organizer->is_verified)
        <div class="alert alert-warning">
            <strong>Organizer belum terverifikasi.</strong>
            Anda dapat membuat dan menyimpan draft, tetapi submit review menunggu verifikasi admin.
        </div>
    @endif

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Total Event', 'value' => $counts->sum(), 'icon' => 'fas fa-calendar-days', 'variant' => 'primary'],
                ['label' => 'Draft', 'value' => (int) ($counts['draft'] ?? 0), 'icon' => 'fas fa-pen-to-square', 'variant' => 'secondary'],
                ['label' => 'Menunggu Review', 'value' => (int) ($counts['pending_review'] ?? 0), 'icon' => 'fas fa-hourglass-half', 'variant' => 'warning'],
                ['label' => 'Published', 'value' => (int) ($counts['published'] ?? 0) + (int) ($counts['sold_out'] ?? 0), 'icon' => 'fas fa-circle-check', 'variant' => 'success'],
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

    <x-layout.modern-card>
        <form method="GET" action="{{ route('organizer.events.index') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari event</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Judul atau slug event">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach ([
                        'draft' => 'Draft',
                        'pending_review' => 'Menunggu Review',
                        'rejected' => 'Ditolak',
                        'published' => 'Published',
                        'sold_out' => 'Sold Out',
                        'ended' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('organizer.events.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Jadwal</th>
                        <th class="text-center">Tiket</th>
                        <th>Status</th>
                        <th style="width: 280px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $event->title }}</div>
                                <div class="small text-muted">
                                    {{ $event->category?->name ?? 'Tanpa kategori' }}
                                    · {{ $event->is_online ? 'Online' : ($event->venue?->name ?? 'Venue menyusul') }}
                                </div>
                            </td>
                            <td class="small">
                                {{ $event->startsAtLocal()?->translatedFormat('d M Y, H.i') ?? '—' }}
                                <div class="text-muted">{{ $event->timezoneLabel() }}</div>
                            </td>
                            <td class="text-center">
                                {{ $event->active_ticket_types_count }}
                            </td>
                            <td>
                                <x-events.status-badge :status="$event->status" />
                                @if ($event->status === 'rejected' && $event->rejection_reason)
                                    <div class="small text-danger mt-2" style="max-width: 240px;">
                                        <strong>Alasan:</strong> {{ $event->rejection_reason }}
                                    </div>
                                @endif
                                @if ($event->status === 'pending_review')
                                    <div class="small text-muted mt-1">Field penjualan terkunci sampai review selesai.</div>
                                @endif
                                @if ($event->exceedsVenueCapacity())
                                    <div class="small text-danger mt-1" style="max-width: 240px;">
                                        <i class="fas fa-triangle-exclamation me-1"></i>
                                        Kuota tiket melebihi kapasitas venue ({{ $event->venue?->capacity }}).
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('organizer.events.edit', $event) }}" class="btn btn-sm btn-outline-primary" title="Edit event">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <a href="{{ route('organizer.events.report', $event) }}" class="btn btn-sm btn-outline-dark" title="Laporan penjualan">
                                        <i class="fas fa-chart-line"></i>
                                    </a>

                                    <a href="{{ route('organizer.events.staff.index', $event) }}" class="btn btn-sm btn-outline-secondary" title="Kelola staff">
                                        <i class="fas fa-user-group"></i>
                                    </a>

                                    @if ($event->isFullyEditable())
                                        <a href="{{ route('organizer.events.tickets.index', $event) }}" class="btn btn-sm btn-outline-secondary" title="Kelola tiket">
                                            <i class="fas fa-ticket"></i>
                                        </a>
                                    @endif

                                    @if (in_array($event->status, ['draft', 'rejected'], true))
                                        <form method="POST" action="{{ route('organizer.events.submit', $event) }}" onsubmit="return confirm('Ajukan event ini untuk review admin? Field penjualan akan terkunci sampai review selesai.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Ajukan review">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($event->isOnSale())
                                        <a href="{{ route('events.show', $event->slug) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat publik">
                                            <i class="fas fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @endif

                                    @if ($event->canBeDeleted())
                                        <form method="POST" action="{{ route('organizer.events.destroy', $event) }}" onsubmit="return confirm('Hapus event draft ini? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                Belum ada event. Klik <strong>Buat Event</strong> untuk memulai draft pertama.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $events->links() }}
        </div>
    </x-layout.modern-card>
</x-layouts.app>
