@php
    $event = $order->event;
    $start = $event?->startsAtLocal();
    $pendingPayment = $order->payments->firstWhere('status', 'pending');
    $canPay = $order->isPending() && ! $expired;
@endphp

<x-layouts.public
    :settings="$settings"
    :title="'Pembayaran '.$order->order_number.' — '.($settings['site_name'] ?? 'LinyTix')"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="eyebrow">Status pembayaran</div>
                    <h1 class="display-6 mb-1">{{ $order->order_number }}</h1>
                    <div class="text-secondary">{{ $order->created_at?->translatedFormat('d M Y, H.i') }}</div>
                </div>
                <div class="text-lg-end">
                    <x-order-status :status="$order->status" />
                    @if ($canPay && $order->expires_at)
                        <div class="small text-secondary mt-2">
                            Bayar sebelum <strong data-countdown="{{ $order->expires_at->toIso8601String() }}">{{ $order->expires_at->translatedFormat('d M Y, H.i') }}</strong>
                            <div class="small" data-countdown-text></div>
                        </div>
                    @endif
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($order->status === 'paid')
                <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>Pembayaran berhasil. E-tiket telah diterbitkan dan dikirim ke email pembeli.</div>
                    <a href="{{ $ticketsUrl }}" class="btn btn-sm btn-main">
                        <i class="fa-solid fa-ticket me-1"></i> Lihat tiket
                    </a>
                </div>
            @elseif ($order->status === 'expired' || $expired)
                <div class="alert alert-secondary d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>Pesanan ini sudah kedaluwarsa karena melewati batas pembayaran.</div>
                    @if ($event)
                        <a href="{{ route('events.show', $event->slug) }}" class="btn btn-sm btn-main">Kembali ke event</a>
                    @endif
                </div>
            @elseif ($order->status === 'cancelled')
                <div class="alert alert-secondary">Pesanan ini dibatalkan.</div>
            @elseif ($order->status === 'refunded')
                <div class="alert alert-info">Dana pesanan ini telah direfund.</div>
            @endif

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Event</h2>
                        <div class="fw-semibold fs-5">{{ $event?->title ?? 'Event' }}</div>
                        <div class="text-secondary small mt-1">
                            <i class="fa-regular fa-calendar me-2"></i>{{ $start?->translatedFormat('d M Y, H.i') }} WIB
                        </div>
                        <div class="text-secondary small">
                            <i class="fa-solid fa-location-dot me-2"></i>
                            {{ $event?->is_online ? 'Online' : ($event?->venue?->name ?? 'Lokasi menyusul') }}
                        </div>
                    </div>

                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Rincian pesanan</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Jenis tiket</th>
                                        <th scope="col" class="text-center">Qty</th>
                                        <th scope="col" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item->ticketType?->name ?? 'Tiket' }}</div>
                                                <div class="small text-secondary">Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }} / tiket</div>
                                            </td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-end">Total</th>
                                        <th class="text-end">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    @if ($canPay)
                        <div class="card-clean p-4 mb-4">
                            <h2 class="h5 mb-3">Pilih metode pembayaran</h2>

                            <form method="POST" action="{{ $payUrl }}">
                                @csrf
                                <div class="row g-2">
                                    @foreach ($paymentMethods as $code => $label)
                                        <div class="col-sm-6">
                                            <label class="payment-option d-flex align-items-center gap-2 p-3">
                                                <input type="radio" class="form-check-input m-0" name="method" value="{{ $code }}" @checked($loop->first) required>
                                                <span class="fw-semibold">{{ $label }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="submit" class="btn btn-main w-100 mt-3">
                                    <i class="fa-solid fa-credit-card me-1"></i> Buat instruksi pembayaran
                                </button>
                            </form>

                            @if ($pendingPayment?->payment_url)
                                <a href="{{ $pendingPayment->payment_url }}" class="btn btn-ghost w-100 mt-2" target="_blank" rel="noopener">
                                    Lanjut ke halaman pembayaran <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    <div class="card-clean p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h2 class="h5 mb-0">Riwayat pembayaran</h2>
                            @if ($order->isPending())
                                <div class="d-flex align-items-center gap-2">
                                    <span class="small text-secondary" data-payment-poll-status>Memeriksa status pembayaran otomatis…</span>
                                    <a href="{{ url()->current() }}" class="btn btn-sm btn-ghost">
                                        <i class="fa-solid fa-rotate me-1"></i> Cek status
                                    </a>
                                </div>
                            @endif
                        </div>

                        @forelse ($order->payments as $payment)
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ $paymentMethods[$payment->method] ?? strtoupper($payment->method) }}</div>
                                    <div class="small text-secondary">{{ $payment->created_at?->translatedFormat('d M Y, H.i') }}</div>
                                </div>
                                <div class="text-end">
                                    <div>Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</div>
                                    <div class="small text-secondary">{{ ucfirst($payment->status) }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">Belum ada percobaan pembayaran.</p>
                        @endforelse
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card-clean p-4">
                        <h2 class="h5 mb-3">Data pembeli</h2>
                        <div class="fw-semibold">{{ $order->buyer_name }}</div>
                        <div class="small text-secondary">{{ $order->buyer_email }}</div>
                        @if ($order->buyer_phone)
                            <div class="small text-secondary">{{ $order->buyer_phone }}</div>
                        @endif
                        @if (! $signed)
                            <a href="{{ route('akun.orders.index') }}" class="text-link d-inline-block mt-3">Lihat semua pesanan saya →</a>
                        @endif
                    </div>

                    <div class="card-clean p-4 mt-4">
                        <h2 class="h5 mb-3">E-tiket</h2>
                        @forelse ($order->tickets as $ticket)
                            <div class="border-bottom py-2">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $ticket->ticketType?->name ?? 'Tiket' }}</div>
                                        <div class="small text-secondary">{{ $ticket->holder_name }}</div>
                                    </div>
                                    <x-order-status :status="$ticket->status" />
                                </div>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">E-tiket diterbitkan setelah pembayaran dikonfirmasi.</p>
                        @endforelse

                        @if ($order->tickets->isNotEmpty())
                            <a href="{{ $ticketsUrl }}" class="btn btn-main w-100 mt-3">Buka e-tiket</a>
                            @if ($pdfUrl)
                                <a href="{{ $pdfUrl }}" class="btn btn-ghost w-100 mt-2">
                                    <i class="fa-solid fa-file-pdf me-1"></i> Unduh PDF e-tiket
                                </a>
                            @endif
                        @endif
                    </div>

                    <div class="card-clean p-4 mt-4">
                        <div class="small text-secondary">Butuh bantuan?</div>
                        <a href="{{ route('faq') }}" class="text-link">Baca FAQ</a>
                        <span class="text-secondary">·</span>
                        <a href="{{ route('faq') }}" class="text-link">Buka FAQ</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <style>
            .payment-option {
                border: 1px solid var(--line);
                border-radius: .9rem;
                cursor: pointer;
                transition: border-color .2s ease, box-shadow .2s ease;
            }

            .payment-option:has(input:checked) {
                border-color: var(--teal);
                
            }
        </style>
    @endpush

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

            @if ($canPay)
                (function () {
                    const statusUrl = @json($statusUrl);
                    const initialStatus = @json($order->status);
                    const interval = 5000;
                    const maxDuration = 15 * 60 * 1000;
                    const label = document.querySelector('[data-payment-poll-status]');
                    let elapsed = 0;
                    let stopped = false;

                    function stop(message) {
                        stopped = true;
                        if (label) {
                            label.textContent = message;
                        }
                    }

                    async function poll() {
                        if (stopped) {
                            return;
                        }

                        if (elapsed >= maxDuration) {
                            stop('Pemeriksaan otomatis berhenti. Gunakan tombol Cek status.');
                            return;
                        }

                        try {
                            const response = await fetch(statusUrl, {
                                headers: { 'Accept': 'application/json' },
                                cache: 'no-store',
                            });

                            if (response.ok) {
                                const data = await response.json();

                                if (data.status !== initialStatus || data.expired) {
                                    window.location.reload();
                                    return;
                                }

                                if (label) {
                                    label.textContent = 'Menunggu pembayaran… (diperiksa otomatis)';
                                }
                            }
                        } catch (error) {
                            // Abaikan gangguan jaringan sementara; polling berlanjut.
                        }

                        elapsed += interval;
                        setTimeout(poll, interval);
                    }

                    setTimeout(poll, interval);
                })();
            @endif
        </script>
    @endpush
</x-layouts.public>
