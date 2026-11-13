<x-layouts.app title="Detail Pesanan - Admin">
    @php
        $latestRefund = $order->refunds->sortByDesc('created_at')->first();
        $canRefund = $order->status === \App\Models\Order::STATUS_PAID && $order->refunds->isEmpty();
        $ticketEmailLog = $order->notificationLogs
            ->where('template', 'ticket_issued')
            ->sortByDesc('created_at')
            ->first();
    @endphp

    <x-layout.page-header title="Pesanan {{ $order->order_number }}" subtitle="{{ $order->event?->title ?? 'Event tidak tersedia' }}">
        <x-slot:actions>
            <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            @if ($order->tickets->isNotEmpty())
                <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-outline-primary">
                    <i class="fas fa-file-pdf me-2"></i>Unduh PDF
                </a>
            @endif
            @if ($order->status === \App\Models\Order::STATUS_PAID && $order->tickets->isNotEmpty())
                <form method="POST" action="{{ route('admin.orders.resend-ticket', $order) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-success">
                        <i class="fas fa-paper-plane me-2"></i>Kirim Ulang E-Tiket
                    </button>
                </form>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <x-layout.modern-card title="Informasi Pesanan" class="mb-4">
                <div class="row g-3 small">
                    <div class="col-md-6">
                        <div class="text-muted">Status pesanan</div>
                        <div class="mt-1"><x-order-status :status="$order->status" /></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Nomor pesanan</div>
                        <div class="fw-semibold">{{ $order->order_number }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Event</div>
                        <div class="fw-semibold">{{ $order->event?->title ?? 'Event tidak tersedia' }}</div>
                        @if ($order->event?->start_at)
                            <div class="text-muted">{{ $order->event->startsAtLocal()?->translatedFormat('d M Y, H.i') }}</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Lokasi</div>
                        <div class="fw-semibold">
                            @if ($order->event?->is_online)
                                Online
                            @else
                                {{ $order->event?->venue?->name ?? 'Venue menyusul' }}
                            @endif
                        </div>
                        @if (! $order->event?->is_online && $order->event?->venue)
                            <div class="text-muted">{{ $order->event->venue->city }}</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Organizer</div>
                        <div class="fw-semibold">{{ $order->event?->organizer?->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Akun pembeli</div>
                        <div class="fw-semibold">{{ $order->user?->name ?? 'Guest checkout' }}</div>
                        @if ($order->user)
                            <div class="text-muted">{{ $order->user->email }}</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Dibuat</div>
                        <div class="fw-semibold">{{ $order->created_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Kedaluwarsa</div>
                        <div class="fw-semibold">{{ $order->expires_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Dibayar</div>
                        <div class="fw-semibold">{{ $order->paid_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                    </div>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Data Pembeli" class="mb-4">
                <div class="row g-3 small">
                    <div class="col-md-6">
                        <div class="text-muted">Nama</div>
                        <div class="fw-semibold">{{ $order->buyer_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Email</div>
                        <div class="fw-semibold">{{ $order->buyer_email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Telepon</div>
                        <div class="fw-semibold">{{ \App\Support\Pii::maskPhone($order->buyer_phone) ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Status email e-tiket</div>
                        @if ($ticketEmailLog)
                            <div class="fw-semibold">
                                <x-ui.badge :variant="match ($ticketEmailLog->status) {
                                    'sent' => 'success',
                                    'failed' => 'danger',
                                    'sending' => 'info',
                                    default => 'warning',
                                }">{{ ucfirst($ticketEmailLog->status) }}</x-ui.badge>
                            </div>
                            <div class="text-muted">
                                {{ $ticketEmailLog->sent_at?->translatedFormat('d M Y, H.i') ?? $ticketEmailLog->created_at?->translatedFormat('d M Y, H.i') }}
                            </div>
                        @else
                            <div class="fw-semibold text-muted">Belum terkirim</div>
                        @endif
                    </div>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Item Pesanan" class="mb-4">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Jenis Tiket</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->ticketType?->name ?? 'Tiket tidak tersedia' }}</div>
                                        @if ($item->ticketType?->description)
                                            <div class="small text-muted">{{ $item->ticketType->description }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada item pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Pembayaran" class="mb-4">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Gateway / Metode</th>
                                <th>Referensi</th>
                                <th class="text-end">Nominal</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->payments as $payment)
                                @php
                                    $paymentVariant = match ($payment->status) {
                                        \App\Models\Payment::STATUS_SUCCESS => 'success',
                                        \App\Models\Payment::STATUS_FAILED => 'danger',
                                        \App\Models\Payment::STATUS_EXPIRED => 'secondary',
                                        \App\Models\Payment::STATUS_REFUNDED => 'info',
                                        default => 'warning',
                                    };
                                    $canReconcile = in_array($payment->status, [
                                        \App\Models\Payment::STATUS_PENDING,
                                        \App\Models\Payment::STATUS_FAILED,
                                        \App\Models\Payment::STATUS_EXPIRED,
                                    ], true);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ ucfirst($payment->gateway) }}</div>
                                        <div class="small text-muted">{{ $payment->method }}</div>
                                    </td>
                                    <td class="small text-muted" style="max-width: 180px;">
                                        <span class="text-break">{{ $payment->gateway_ref ?? '—' }}</span>
                                    </td>
                                    <td class="text-end fw-semibold">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                                    <td><x-ui.badge :variant="$paymentVariant">{{ ucfirst($payment->status) }}</x-ui.badge></td>
                                    <td class="small">
                                        <div>Dibuat: {{ $payment->created_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                                        <div>Dibayar: {{ $payment->paid_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                                        <div>Kedaluwarsa: {{ $payment->expires_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                                    </td>
                                    <td>
                                        @if ($canReconcile)
                                            <form method="POST" action="{{ route('admin.orders.payments.reconcile', [$order, $payment]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-rotate me-1"></i>Rekonsiliasi
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0 mt-3">
                    Rekonsiliasi manual memeriksa status terbaru ke gateway. Nomor identitas dan telepon dimasking pada halaman ini.
                </p>
            </x-layout.modern-card>

            <x-layout.modern-card title="Tiket" class="mb-4">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pemegang</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Diterbitkan</th>
                                <th>Check-in</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->tickets as $ticket)
                                @php
                                    $ticketVariant = match ($ticket->status) {
                                        \App\Models\Ticket::STATUS_ACTIVE => 'success',
                                        \App\Models\Ticket::STATUS_USED => 'info',
                                        \App\Models\Ticket::STATUS_CANCELLED => 'secondary',
                                        \App\Models\Ticket::STATUS_REFUNDED => 'danger',
                                        default => 'warning',
                                    };
                                    $checkIn = $ticket->checkIns->sortByDesc('scanned_at')->first();
                                @endphp
                                <tr>
                                    <td class="small font-monospace text-break" style="max-width: 180px;">{{ $ticket->ticket_code }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $ticket->holder_name }}</div>
                                        <div class="small text-muted">{{ \App\Support\Pii::maskIdentity($ticket->holder_id_number) ?? 'Identitas —' }}</div>
                                    </td>
                                    <td class="small">{{ $ticket->ticketType?->name ?? '—' }}</td>
                                    <td><x-ui.badge :variant="$ticketVariant">{{ ucfirst($ticket->status) }}</x-ui.badge></td>
                                    <td class="small">{{ $ticket->issued_at?->translatedFormat('d M Y, H.i') ?? '—' }}</td>
                                    <td class="small">
                                        @if ($checkIn)
                                            <div>{{ $checkIn->scanned_at?->translatedFormat('d M Y, H.i') }}</div>
                                            <div class="text-muted">{{ $checkIn->gate ?: 'Gate —' }} · {{ $checkIn->result }}</div>
                                        @else
                                            <span class="text-muted">Belum check-in</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada tiket yang diterbitkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Riwayat Refund">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Alasan</th>
                                <th>Diminta</th>
                                <th>Diproses</th>
                                <th style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->refunds->sortByDesc('created_at') as $refund)
                                @php
                                    $refundVariant = match ($refund->status) {
                                        \App\Models\Refund::STATUS_SUCCEEDED => 'success',
                                        \App\Models\Refund::STATUS_FAILED => 'danger',
                                        \App\Models\Refund::STATUS_PROCESSING => 'info',
                                        default => 'warning',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold">Rp {{ number_format((int) $refund->amount, 0, ',', '.') }}</td>
                                    <td>
                                        <x-ui.badge :variant="$refundVariant">{{ ucfirst($refund->status) }}</x-ui.badge>
                                        @if ($refund->error)
                                            <div class="small text-danger mt-1" style="max-width: 220px;" title="{{ $refund->error }}">
                                                {{ \Illuminate\Support\Str::limit($refund->error, 120) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="small" style="max-width: 260px;">{{ $refund->reason }}</td>
                                    <td class="small">
                                        <div>{{ $refund->requester?->name ?? '—' }}</div>
                                        <div class="text-muted">{{ $refund->created_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                                    </td>
                                    <td class="small">
                                        <div>{{ $refund->processor?->name ?? '—' }}</div>
                                        <div class="text-muted">{{ $refund->processed_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                                    </td>
                                    <td>
                                        @if (in_array($refund->status, [\App\Models\Refund::STATUS_REQUESTED, \App\Models\Refund::STATUS_FAILED], true))
                                            <form method="POST" action="{{ route('admin.refunds.retry', $refund) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-rotate-right me-1"></i>Coba Lagi
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat refund.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-layout.modern-card>
        </div>

        <div class="col-lg-4">
            <x-layout.modern-card title="Ringkasan Biaya" class="mb-4">
                <dl class="row small mb-0">
                    <dt class="col-7 text-muted fw-normal">Subtotal</dt>
                    <dd class="col-5 text-end mb-2">Rp {{ number_format((int) $order->subtotal, 0, ',', '.') }}</dd>

                    <dt class="col-7 text-muted fw-normal">Diskon</dt>
                    <dd class="col-5 text-end mb-2">- Rp {{ number_format((int) $order->discount_amount, 0, ',', '.') }}</dd>

                    <dt class="col-7 text-muted fw-normal">Biaya admin</dt>
                    <dd class="col-5 text-end mb-2">Rp {{ number_format((int) $order->admin_fee, 0, ',', '.') }}</dd>

                    <dt class="col-7 text-muted fw-normal">Biaya pembayaran</dt>
                    <dd class="col-5 text-end mb-2">Rp {{ number_format((int) $order->payment_fee, 0, ',', '.') }}</dd>

                    <dt class="col-7 text-muted fw-normal">Pajak</dt>
                    <dd class="col-5 text-end mb-2">Rp {{ number_format((int) $order->tax_amount, 0, ',', '.') }}</dd>

                    <dt class="col-7 fw-semibold border-top pt-2">Total</dt>
                    <dd class="col-5 text-end fw-bold border-top pt-2">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</dd>
                </dl>
            </x-layout.modern-card>

            <x-layout.modern-card title="Refund Penuh">
                @if ($canRefund)
                    <p class="small text-muted">
                        Refund penuh sebesar <strong>Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</strong>.
                        Tiket akan ditandai refunded setelah gateway berhasil. Kuota tiket tidak dikembalikan.
                    </p>
                    <form method="POST" action="{{ route('admin.orders.refund', $order) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="refund-reason" class="form-label small fw-semibold">Alasan refund</label>
                            <textarea id="refund-reason" name="reason" class="form-control" rows="3" minlength="5" maxlength="1000" required placeholder="Contoh: Permintaan pembeli karena event dijadwalkan ulang"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Ajukan refund penuh untuk pesanan ini?')">
                            <i class="fas fa-rotate-left me-1"></i>Ajukan Refund
                        </button>
                    </form>
                @elseif ($latestRefund)
                    <p class="small text-muted mb-2">Refund terakhir:</p>
                    <div class="mb-2">
                        <x-ui.badge :variant="match ($latestRefund->status) {
                            \App\Models\Refund::STATUS_SUCCEEDED => 'success',
                            \App\Models\Refund::STATUS_FAILED => 'danger',
                            \App\Models\Refund::STATUS_PROCESSING => 'info',
                            default => 'warning',
                        }">{{ ucfirst($latestRefund->status) }}</x-ui.badge>
                    </div>
                    <p class="small text-muted mb-3">
                        Rp {{ number_format((int) $latestRefund->amount, 0, ',', '.') }} ·
                        {{ $latestRefund->created_at?->translatedFormat('d M Y, H.i') }}
                    </p>
                    @if (in_array($latestRefund->status, [\App\Models\Refund::STATUS_REQUESTED, \App\Models\Refund::STATUS_FAILED], true))
                        <form method="POST" action="{{ route('admin.refunds.retry', $latestRefund) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-rotate-right me-1"></i>Proses Ulang Refund
                            </button>
                        </form>
                    @endif
                @else
                    <p class="small text-muted mb-0">
                        Refund hanya tersedia untuk pesanan berstatus lunas dan belum memiliki refund.
                    </p>
                @endif
            </x-layout.modern-card>
        </div>
    </div>
</x-layouts.app>
