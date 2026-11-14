@php
    $event = $order->event;
    $start = $event?->startsAtLocal();
    $latestPayment = $order->payments->first();
@endphp

<x-layouts.public
    :settings="$settings"
    :title="'Pesanan '.$order->order_number.' — '.($settings['site_name'] ?? 'LinyTix')"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('akun.dashboard') }}">Akun</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('akun.orders.index') }}">Pesanan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $order->order_number }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="eyebrow">Detail pesanan</div>
                    <h1 class="display-6 mb-1">{{ $order->order_number }}</h1>
                    <div class="text-secondary">{{ $order->created_at?->translatedFormat('d M Y, H.i') }}</div>
                </div>
                <div class="text-lg-end">
                    <x-order-status :status="$order->status" />
                    @if ($order->status === 'pending' && $order->expires_at)
                        <div class="small text-secondary mt-2">
                            Bayar sebelum <strong data-countdown="{{ $order->expires_at->toIso8601String() }}">{{ $order->expires_at->translatedFormat('d M Y, H.i') }}</strong>
                            <div class="small" data-countdown-text></div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($order->status === 'pending')
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>Selesaikan pembayaran sebelum batas waktu agar pesanan tidak kedaluwarsa.</div>
                    @if ($latestPayment?->payment_url)
                        <a href="{{ $latestPayment->payment_url }}" class="btn btn-sm btn-main" rel="noopener">Lanjut bayar</a>
                    @else
                        <button class="btn btn-sm btn-secondary" disabled>Lanjut bayar</button>
                    @endif
                </div>
            @elseif ($order->status === 'expired')
                <div class="alert alert-secondary">Pesanan kedaluwarsa karena melewati batas pembayaran.</div>
            @elseif ($order->status === 'cancelled')
                <div class="alert alert-secondary">Pesanan ini dibatalkan.</div>
            @elseif ($order->status === 'refunded')
                <div class="alert alert-info">Dana pesanan ini telah direfund.</div>
            @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Event</h2>
                        <div class="fw-semibold fs-5">{{ $event?->title ?? 'Event' }}</div>
                        <div class="text-secondary small mt-1">
                            <i class="fa-regular fa-calendar me-2"></i>{{ $start?->translatedFormat('d M Y, H.i') }} {{ $start?->format('T') }}
                        </div>
                        <div class="text-secondary small">
                            <i class="fa-solid fa-location-dot me-2"></i>
                            {{ $event?->is_online ? 'Online' : ($event->venue?->name ?? 'Lokasi menyusul') }}
                        </div>
                        @if ($event)
                            <a href="{{ route('events.show', $event->slug) }}" class="text-link d-inline-block mt-3">Lihat halaman event →</a>
                        @endif
                    </div>

                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Item</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Jenis tiket</th>
                                        <th scope="col" class="text-end">Harga</th>
                                        <th scope="col" class="text-center">Qty</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td>{{ $item->ticketType?->name ?? 'Tiket' }}</td>
                                            <td class="text-end">Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th class="text-end">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="card-clean p-4">
                        <h2 class="h5 mb-3">Pembayaran</h2>
                        @forelse ($order->payments as $payment)
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ strtoupper($payment->method ?? $payment->gateway) }}</div>
                                    <div class="small text-secondary">{{ $payment->created_at?->translatedFormat('d M Y, H.i') }}</div>
                                </div>
                                <div class="text-end">
                                    <div>Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</div>
                                    <div class="small text-secondary">{{ ucfirst($payment->status) }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">Belum ada data pembayaran.</p>
                        @endforelse
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-clean p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="h5 mb-0">E-tiket</h2>
                            @if ($order->tickets->isNotEmpty())
                                <a href="{{ route('akun.orders.pdf', $order) }}" class="btn btn-sm btn-main">
                                    <i class="fa-solid fa-file-pdf me-1"></i> Unduh PDF
                                </a>
                            @endif
                        </div>
                        @forelse ($order->tickets as $ticket)
                            <a href="{{ route('akun.tickets.show', $ticket) }}" class="d-flex justify-content-between align-items-center gap-2 border-bottom py-2 text-reset text-decoration-none">
                                <div>
                                    <div class="fw-semibold">{{ $ticket->ticketType?->name ?? 'Tiket' }}</div>
                                    <div class="small text-secondary">{{ $ticket->holder_name }}</div>
                                </div>
                                <x-order-status :status="$ticket->status" />
                            </a>
                        @empty
                            <p class="text-secondary mb-0">
                                E-tiket diterbitkan setelah pembayaran dikonfirmasi.
                            </p>
                        @endforelse
                    </div>

                    <div class="card-clean p-4 mt-4">
                        <div class="small text-secondary">Pemesan</div>
                        <div class="fw-semibold">{{ $order->buyer_name }}</div>
                        <div class="small text-secondary">{{ $order->buyer_email }}</div>
                        @if ($order->buyer_phone)
                            <div class="small text-secondary">{{ $order->buyer_phone }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-countdown]').forEach(function (el) {
                const target = new Date(el.dataset.countdown).getTime();
                const output = el.parentElement.querySelector('[data-countdown-text]');

                function tick() {
                    const diff = target - Date.now();
                    if (!output) return;
                    if (diff <= 0) {
                        output.textContent = 'Waktu pembayaran habis.';
                        return;
                    }
                    const minutes = Math.floor(diff / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    output.textContent = 'Sisa ' + minutes + ' menit ' + seconds + ' detik.';
                    setTimeout(tick, 1000);
                }

                tick();
            });
        </script>
    @endpush
</x-layouts.public>
