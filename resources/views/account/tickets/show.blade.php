@php
    $event = $ticket->event;
    $order = $ticket->order;
    $downloadable = $ticket->status === \App\Models\Ticket::STATUS_ACTIVE;
@endphp

<x-layouts.public
    :settings="$settings"
    :title="'E-Tiket '.$ticket->ticket_code.' — '.($settings['site_name'] ?? 'LinyTix')"
>
    <style>
        @media print {
            .navbar, .footer, .whatsapp, .no-print { display: none !important; }
            body { background: #fff !important; }
            .ticket-sheet { box-shadow: none !important; border: 1px solid #ddd !important; }
            .section { padding: 0 !important; }
        }
    </style>

    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 no-print">
                <div>
                    <nav aria-label="breadcrumb" class="mb-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('akun.dashboard') }}">Akun</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('akun.tickets.index') }}">Tiket saya</a></li>
                            <li class="breadcrumb-item active" aria-current="page">E-tiket</li>
                        </ol>
                    </nav>
                    <h1 class="display-6 mb-0">E-tiket</h1>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('akun.orders.show', $order) }}" class="btn btn-ghost">Lihat pesanan</a>
                    <a href="{{ route('akun.tickets.pdf', $ticket) }}" class="btn btn-main">
                        <i class="fa-solid fa-file-pdf me-1"></i> Unduh PDF
                    </a>
                    <button type="button" class="btn btn-ghost" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Cetak
                    </button>
                </div>
            </div>

            @if (! $downloadable)
                <div class="alert alert-secondary no-print">
                    Tiket ini berstatus <strong>{{ $ticket->status }}</strong> dan tidak dapat digunakan untuk masuk.
                </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-clean ticket-sheet p-4 p-md-5" style="border-radius: 1.5rem;">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 border-bottom pb-4 mb-4">
                            <div>
                                <div class="eyebrow">{{ $settings['site_name'] ?? 'LinyTix' }}</div>
                                <h2 class="h3 mb-1">{{ $event?->title ?? 'Event' }}</h2>
                                <div class="text-secondary">
                                    <i class="fa-regular fa-calendar me-2"></i>{{ $event?->startsAtLocal()?->translatedFormat('l, d F Y, H.i') }} WIB
                                </div>
                                <div class="text-secondary">
                                    <i class="fa-solid fa-location-dot me-2"></i>
                                    {{ $event?->is_online ? 'Online' : ($event?->venue?->name ?? 'Lokasi menyusul') }}
                                </div>
                            </div>
                            <x-order-status :status="$ticket->status" />
                        </div>

                        <div class="row g-4 align-items-center">
                            <div class="col-sm-5 text-center">
                                <div class="d-inline-block bg-white p-2 border rounded-3">
                                    {!! $qrSvg !!}
                                </div>
                                <div class="small text-secondary mt-2">Tunjukkan QR ini di pintu masuk</div>
                            </div>
                            <div class="col-sm-7">
                                <dl class="row mb-0">
                                    <dt class="col-5 text-secondary fw-normal">Kode tiket</dt>
                                    <dd class="col-7 font-monospace text-break">{{ $ticket->ticket_code }}</dd>

                                    <dt class="col-5 text-secondary fw-normal">Jenis tiket</dt>
                                    <dd class="col-7">{{ $ticket->ticketType?->name ?? 'Tiket' }}</dd>

                                    <dt class="col-5 text-secondary fw-normal">Nama pemegang</dt>
                                    <dd class="col-7">{{ $ticket->holder_name }}</dd>

                                    <dt class="col-5 text-secondary fw-normal">Nomor order</dt>
                                    <dd class="col-7">{{ $order?->order_number }}</dd>

                                    <dt class="col-5 text-secondary fw-normal">Status</dt>
                                    <dd class="col-7"><x-order-status :status="$ticket->status" /></dd>

                                    @if ($ticket->used_at)
                                        <dt class="col-5 text-secondary fw-normal">Dipakai pada</dt>
                                        <dd class="col-7">{{ $ticket->used_at->translatedFormat('d M Y, H.i') }}</dd>
                                    @endif
                                </dl>
                            </div>
                        </div>

                        <div class="border-top mt-4 pt-3 small text-secondary">
                            Tiket bersifat pribadi dan hanya berlaku untuk event yang tertera. Pemalsuan atau penggunaan ganda dapat menyebabkan tiket tidak berlaku.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
