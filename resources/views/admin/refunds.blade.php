<x-layouts.app title="Refund - Admin">
    <x-layout.page-header title="Refund" subtitle="Kelola pengembalian dana, pantau kegagalan, dan batalkan event.">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('admin.orders') }}">
                <i class="fas fa-receipt me-2"></i>Pesanan
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="row g-3 mb-4">
        @php
            $statusCards = [
                'requested' => ['Diminta', 'warning'],
                'processing' => ['Diproses', 'info'],
                'succeeded' => ['Berhasil', 'success'],
                'failed' => ['Gagal', 'danger'],
            ];
        @endphp
        @foreach ($statusCards as $key => [$label, $variant])
            <div class="col-6 col-lg-3">
                <div class="modern-card h-100">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="h3 mb-0 fw-bold">{{ (int) ($counts[$key] ?? 0) }}</div>
                        </div>
                        <x-ui.badge :variant="$variant">{{ $label }}</x-ui.badge>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <x-layout.modern-card title="Daftar Refund" class="mb-4">
        <form method="GET" action="{{ route('admin.refunds') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari refund</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Nomor pesanan, nama, atau email">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach (['requested' => 'Diminta', 'processing' => 'Diproses', 'succeeded' => 'Berhasil', 'failed' => 'Gagal'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('admin.refunds') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Pesanan</th>
                        <th>Event</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Diminta</th>
                        <th>Diproses</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refunds as $refund)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $refund->order?->order_number ?? '—' }}</div>
                                <small class="text-muted">{{ $refund->order?->buyer_name }}</small>
                                <div class="small text-muted">{{ $refund->order?->buyer_email }}</div>
                            </td>
                            <td class="text-secondary">{{ $refund->order?->event?->title ?? 'Event tidak tersedia' }}</td>
                            <td class="fw-semibold">Rp {{ number_format((int) $refund->amount, 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $variant = match ($refund->status) {
                                        'succeeded' => 'success',
                                        'failed' => 'danger',
                                        'processing' => 'info',
                                        default => 'warning',
                                    };
                                @endphp
                                <x-ui.badge :variant="$variant">{{ ucfirst($refund->status) }}</x-ui.badge>
                                @if ($refund->error)
                                    <div class="small text-danger mt-1" style="max-width: 220px;" title="{{ $refund->error }}">
                                        {{ \Illuminate\Support\Str::limit($refund->error, 120) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="small">{{ $refund->requester?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $refund->created_at?->translatedFormat('d M Y, H.i') }}</div>
                            </td>
                            <td>
                                <div class="small">{{ $refund->processor?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $refund->processed_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                            </td>
                            <td>
                                @if (in_array($refund->status, ['requested', 'failed'], true))
                                    <form method="POST" action="{{ route('admin.refunds.retry', $refund) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $refund->status === 'failed' ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                            <i class="fas fa-rotate-right me-1"></i>{{ $refund->status === 'failed' ? 'Coba lagi' : 'Proses' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada refund yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $refunds->links() }}
        </div>
    </x-layout.modern-card>

    <x-layout.modern-card title="Batalkan Event">
        <p class="text-muted">
            Pembatalan event akan menandai event sebagai dibatalkan dan mengajukan refund penuh untuk seluruh pesanan lunas.
            Stok tiket tidak dikembalikan untuk mencegah overselling.
        </p>

        @if ($cancellableEvents->isEmpty())
            <div class="alert alert-info mb-0">Tidak ada event aktif yang dapat dibatalkan.</div>
        @else
            <form method="POST" action="{{ route('admin.events.cancel') }}" class="row g-3">
                @csrf
                <div class="col-md-5">
                    <label for="event_id" class="form-label small fw-semibold">Event</label>
                    <select id="event_id" name="event_id" class="form-select" required>
                        <option value="">Pilih event</option>
                        @foreach ($cancellableEvents as $event)
                            <option value="{{ $event->getKey() }}">
                                {{ $event->title }} — {{ ucfirst($event->status) }}@if ($event->start_at) ({{ $event->start_at->translatedFormat('d M Y') }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="cancel_reason" class="form-label small fw-semibold">Alasan pembatalan</label>
                    <input type="text" id="cancel_reason" name="reason" class="form-control" minlength="5" maxlength="1000" required placeholder="Contoh: Event dibatalkan karena force majeure">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Batalkan event ini dan refund semua pesanan lunas?')">
                        <i class="fas fa-ban me-1"></i>Batalkan
                    </button>
                </div>
            </form>
        @endif
    </x-layout.modern-card>
</x-layouts.app>
