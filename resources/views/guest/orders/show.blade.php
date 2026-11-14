@php
    $event = $order->event;
    $start = $event?->startsAtLocal();
@endphp

<x-layouts.public
    :settings="$settings"
    :title="'Pesanan '.$order->order_number.' — '.($settings['site_name'] ?? 'LinyTix')"
>
    <style>
        @media print {
            .navbar, .footer, .whatsapp, .no-print { display: none !important; }
            body { background: #fff !important; }
            .section { padding: 0 !important; }
        }
    </style>

    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="eyebrow">Akses guest</div>
                    <h1 class="display-6 mb-1">{{ $order->order_number }}</h1>
                    <div class="text-secondary">{{ $order->created_at?->translatedFormat('d M Y, H.i') }}</div>
                </div>
                <div class="text-lg-end">
                    <x-order-status :status="$order->status" />
                    <div class="small text-secondary mt-2 no-print">
                        Tautan berlaku hingga {{ $accessExpiresAt->translatedFormat('d M Y') }}
                    </div>
                </div>
            </div>

            <div class="alert alert-light border no-print">
                Tautan ini bersifat pribadi. Jangan bagikan kepada orang lain.
                <a href="{{ route('guest.orders.lookup') }}" class="text-link">Minta tautan baru</a>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Event</h2>
                        <div class="fw-semibold fs-5">{{ $event?->title ?? 'Event' }}</div>
                        <div class="text-secondary small mt-1">
                            <i class="fa-regular fa-calendar me-2"></i>{{ $start?->translatedFormat('d M Y, H.i') }} {{ $start?->format('T') }}
                        </div>
                        <div class="text-secondary small">
                            <i class="fa-solid fa-location-dot me-2"></i>
                            {{ $event?->is_online ? 'Online' : ($event?->venue?->name ?? 'Lokasi menyusul') }}
                        </div>
                    </div>

                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Item</h2>
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
                                            <td>{{ $item->ticketType?->name ?? 'Tiket' }}</td>
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

                    <div class="card-clean p-4">
                        <h2 class="h5 mb-2">Data pemesan</h2>
                        <div class="fw-semibold">{{ $order->buyer_name }}</div>
                        <div class="small text-secondary">{{ $order->buyer_email }}</div>
                        @if ($order->buyer_phone)
                            <div class="small text-secondary">{{ $order->buyer_phone }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card-clean p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">E-tiket</h2>
                            <div class="d-flex gap-2">
                                @if ($pdfUrl)
                                    <a href="{{ $pdfUrl }}" class="btn btn-sm btn-main">
                                        <i class="fa-solid fa-file-pdf me-1"></i> Unduh PDF
                                    </a>
                                @endif
                                @if ($order->tickets->contains(fn ($ticket) => $ticket->status === 'active'))
                                    <button type="button" class="btn btn-sm btn-ghost no-print" onclick="window.print()">
                                        <i class="fa-solid fa-print me-1"></i> Cetak
                                    </button>
                                @endif
                            </div>
                        </div>

                        @forelse ($order->tickets as $ticket)
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $ticket->ticketType?->name ?? 'Tiket' }}</div>
                                        <div class="small text-secondary">{{ $ticket->holder_name }}</div>
                                    </div>
                                    <x-order-status :status="$ticket->status" />
                                </div>

                                @if ($ticket->status === 'active')
                                    <div class="text-center my-3">
                                        <div class="d-inline-block bg-white p-2 border rounded-3">
                                            {!! \App\Support\QrCode::svg($ticket->ticket_code, 180) !!}
                                        </div>
                                    </div>
                                @endif

                                <div class="small text-secondary font-monospace text-break">{{ $ticket->ticket_code }}</div>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">E-tiket diterbitkan setelah pembayaran dikonfirmasi.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
