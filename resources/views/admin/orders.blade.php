<x-layouts.app title="Pesanan & E-Tiket - Admin">
    <x-layout.page-header title="Pesanan & E-Tiket" subtitle="Pantau pesanan dan kirim ulang email e-tiket yang gagal.">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('events.index') }}" target="_blank">
                <i class="fas fa-ticket me-2"></i>Lihat Event
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

    <x-layout.modern-card>
        <form method="GET" action="{{ route('admin.orders') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari pesanan</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Nomor pesanan, nama, atau email">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach (['pending' => 'Menunggu pembayaran', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'refunded' => 'Direfund'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Pesanan</th>
                        <th>Event</th>
                        <th>Pembeli</th>
                        <th>Status</th>
                        <th class="text-center">Tiket</th>
                        <th>Email e-tiket</th>
                        <th>Refund</th>
                        <th style="width: 190px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php $log = $notificationLogs->get($order->getKey()); @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $order->order_number }}</div>
                                <small class="text-muted">{{ $order->created_at?->translatedFormat('d M Y, H.i') }}</small>
                            </td>
                            <td class="text-secondary">{{ $order->event?->title ?? 'Event tidak tersedia' }}</td>
                            <td>
                                <div class="fw-semibold text-body">{{ $order->buyer_name }}</div>
                                <small class="text-muted">{{ $order->buyer_email }}</small>
                            </td>
                            <td><x-order-status :status="$order->status" /></td>
                            <td class="text-center">{{ $order->tickets_count }}</td>
                            <td>
                                @if ($log)
                                    @php
                                        $variant = match ($log->status) {
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                            'sending' => 'info',
                                            default => 'warning',
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$variant">{{ ucfirst($log->status) }}</x-ui.badge>
                                    <div class="small text-muted mt-1">
                                        {{ $log->sent_at?->translatedFormat('d M Y, H.i') ?? $log->created_at?->translatedFormat('d M Y, H.i') }}
                                    </div>
                                    @if ($log->status === 'failed' && $log->error)
                                        <div class="small text-danger text-truncate" style="max-width: 220px;" title="{{ $log->error }}">
                                            {{ $log->error }}
                                        </div>
                                    @endif
                                @elseif ($order->tickets_count > 0)
                                    <x-ui.badge variant="warning">Belum terkirim</x-ui.badge>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php $refund = $order->refunds->sortByDesc('created_at')->first(); @endphp
                                @if ($refund)
                                    @php
                                        $refundVariant = match ($refund->status) {
                                            'succeeded' => 'success',
                                            'failed' => 'danger',
                                            'processing' => 'info',
                                            default => 'warning',
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$refundVariant">{{ ucfirst($refund->status) }}</x-ui.badge>
                                    @if ($refund->error)
                                        <div class="small text-danger text-truncate mt-1" style="max-width: 160px;" title="{{ $refund->error }}">
                                            {{ $refund->error }}
                                        </div>
                                    @endif
                                    @if (in_array($refund->status, ['requested', 'failed'], true))
                                        <form method="POST" action="{{ route('admin.refunds.retry', $refund) }}" class="mt-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Coba lagi</button>
                                        </form>
                                    @endif
                                @elseif ($order->status === 'paid')
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#refundModal-{{ $order->getKey() }}">
                                        <i class="fas fa-rotate-left me-1"></i>Refund
                                    </button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if ($order->tickets_count > 0)
                                        <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-sm btn-outline-primary" title="Unduh PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endif
                                    @if ($order->status === 'paid' && $order->tickets_count > 0)
                                        <form method="POST" action="{{ route('admin.orders.resend-ticket', $order) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Kirim ulang email e-tiket">
                                                <i class="fas fa-paper-plane me-1"></i>Kirim ulang
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Tidak ada pesanan yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    </x-layout.modern-card>

    @foreach ($orders as $order)
        @if ($order->status === 'paid' && $order->refunds->isEmpty())
            <div class="modal fade" id="refundModal-{{ $order->getKey() }}" tabindex="-1" aria-labelledby="refundModalLabel-{{ $order->getKey() }}" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="refundModalLabel-{{ $order->getKey() }}">Refund {{ $order->order_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                Refund penuh sebesar <strong>Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</strong>.
                                Tiket akan ditandai refunded setelah gateway berhasil. Kuota tiket tidak dikembalikan.
                            </p>
                            <label for="reason-{{ $order->getKey() }}" class="form-label small fw-semibold">Alasan refund</label>
                            <textarea id="reason-{{ $order->getKey() }}" name="reason" class="form-control" rows="3" minlength="5" maxlength="1000" required placeholder="Contoh: Permintaan pembeli karena event dijadwalkan ulang"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-rotate-left me-1"></i>Ajukan Refund
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
</x-layouts.app>
