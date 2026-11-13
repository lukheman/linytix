<x-layouts.app title="Moderasi Event - Admin">
    <x-layout.page-header title="Moderasi Event" subtitle="Tinjau event yang diajukan organizer, lalu publikasikan atau tolak dengan alasan.">
        <x-slot:actions>
            <a href="{{ route('events.index') }}" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-arrow-up-right-from-square me-2"></i>Lihat Publik
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

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Menunggu Review', 'value' => (int) ($counts['pending_review'] ?? 0), 'variant' => 'warning'],
                ['label' => 'Published', 'value' => (int) ($counts['published'] ?? 0) + (int) ($counts['sold_out'] ?? 0), 'variant' => 'success'],
                ['label' => 'Ditolak', 'value' => (int) ($counts['rejected'] ?? 0), 'variant' => 'danger'],
                ['label' => 'Total Event', 'value' => $counts->sum(), 'variant' => 'primary'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="col-6 col-lg-3">
                <div class="modern-card h-100">
                    <div class="text-muted small">{{ $card['label'] }}</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="h3 mb-0 fw-bold">{{ $card['value'] }}</div>
                        <x-ui.badge :variant="$card['variant']"><i class="fas fa-circle"></i></x-ui.badge>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <x-layout.modern-card>
        <form method="GET" action="{{ route('admin.events') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari event</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Judul, slug, atau organizer">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach ([
                        'pending_review' => 'Menunggu Review',
                        'published' => 'Published',
                        'rejected' => 'Ditolak',
                        'sold_out' => 'Sold Out',
                        'ended' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        'draft' => 'Draft',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('admin.events') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Organizer</th>
                        <th>Jadwal</th>
                        <th>Status</th>
                        <th style="width: 320px;">Moderasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    <a href="{{ route('admin.events.show', $event) }}" class="text-decoration-none">
                                        {{ $event->title }}
                                    </a>
                                </div>
                                <div class="small text-muted">
                                    {{ $event->category?->name ?? 'Tanpa kategori' }}
                                    · {{ $event->is_online ? 'Online' : ($event->venue?->name ?? 'Venue menyusul') }}
                                </div>
                                @if ($event->status === 'rejected' && $event->rejection_reason)
                                    <div class="small text-danger mt-1"><strong>Alasan:</strong> {{ $event->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-semibold">{{ $event->organizer?->name ?? '—' }}</div>
                                <div class="small text-muted">
                                    @if ($event->organizer?->is_verified)
                                        <span class="text-success"><i class="fas fa-circle-check me-1"></i>Terverifikasi</span>
                                    @else
                                        <span class="text-warning"><i class="fas fa-triangle-exclamation me-1"></i>Belum terverifikasi</span>
                                    @endif
                                </div>
                            </td>
                            <td class="small">
                                {{ $event->startsAtLocal()?->translatedFormat('d M Y, H.i') ?? '—' }}
                                <div class="text-muted">{{ $event->timezoneLabel() }}</div>
                            </td>
                            <td>
                                <x-events.status-badge :status="$event->status" />
                                @if ($event->published_at)
                                    <div class="small text-muted mt-1">{{ $event->published_at->translatedFormat('d M Y, H.i') }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($event->status === 'pending_review')
                                    <div class="d-flex flex-column gap-2">
                                        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-primary w-100">
                                            <i class="fas fa-magnifying-glass me-1"></i>Review Detail
                                        </a>
                                        <form method="POST" action="{{ route('admin.events.approve', $event) }}" onsubmit="return confirm('Publikasikan event ini?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success w-100">
                                                <i class="fas fa-check me-1"></i>Setujui & Publikasikan
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.events.reject', $event) }}" class="d-flex flex-column gap-1">
                                            @csrf
                                            <textarea name="rejection_reason" rows="2" class="form-control form-control-sm" maxlength="2000" placeholder="Alasan penolakan (wajib)" required></textarea>
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="fas fa-xmark me-1"></i>Tolak & Kembalikan
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.events.staff.index', $event) }}" class="btn btn-sm btn-outline-secondary w-100">
                                            <i class="fas fa-user-group me-1"></i>Kelola Staff
                                        </a>
                                    </div>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-magnifying-glass me-1"></i>Detail
                                        </a>
                                        @if ($event->isOnSale())
                                            <a href="{{ route('events.show', $event->slug) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-arrow-up-right-from-square me-1"></i>Lihat
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.events.staff.index', $event) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-user-group me-1"></i>Kelola Staff
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Belum ada event yang cocok dengan filter.</td>
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
