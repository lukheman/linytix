<x-layouts.public
    :settings="$settings"
    :title="'Tiket Saya — '.($settings['site_name'] ?? 'LinyTix')"
    :canonical="route('akun.tickets.index')"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <div class="eyebrow">Akun</div>
                    <h1 class="display-6 mb-0">Tiket saya</h1>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('akun.dashboard') }}" class="btn btn-ghost">Akun</a>
                    <a href="{{ route('akun.orders.index') }}" class="btn btn-main">Pesanan saya</a>
                </div>
            </div>

            @if ($groupedTickets->isEmpty())
                <div class="card-clean p-5 text-center">
                    <div class="icon-box mx-auto mb-3"><i class="fa-solid fa-ticket"></i></div>
                    <h2 class="h5">Belum ada tiket</h2>
                    <p class="text-secondary mb-3">Tiket akan muncul setelah pembayaran dikonfirmasi.</p>
                    <a href="{{ route('events.index') }}" class="btn btn-main">Jelajahi event</a>
                </div>
            @else
                <div class="d-flex flex-column gap-4">
                    @foreach ($groupedTickets as $tickets)
                        @php $event = $tickets->first()->event; @endphp
                        <div class="card-clean p-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">{{ $event?->title ?? 'Event' }}</h2>
                                    <div class="small text-secondary">
                                        <i class="fa-regular fa-calendar me-2"></i>
                                        {{ $event?->startsAtLocal()?->translatedFormat('d M Y, H.i') }} WIB
                                        <span class="mx-2">·</span>
                                        {{ $event?->is_online ? 'Online' : ($event->venue?->city ?? 'Lokasi menyusul') }}
                                    </div>
                                </div>
                                @if ($event)
                                    <a href="{{ route('events.show', $event->slug) }}" class="btn btn-sm btn-ghost">Detail event</a>
                                @endif
                            </div>

                            <div class="row g-3">
                                @foreach ($tickets as $ticket)
                                    <div class="col-md-6">
                                        <a href="{{ route('akun.tickets.show', $ticket) }}" class="card-clean p-3 d-flex justify-content-between align-items-center gap-2 text-reset text-decoration-none h-100">
                                            <div>
                                                <div class="fw-semibold">{{ $ticket->ticketType?->name ?? 'Tiket' }}</div>
                                                <div class="small text-secondary">{{ $ticket->holder_name }}</div>
                                                <div class="small text-secondary font-monospace">{{ \Illuminate\Support\Str::limit($ticket->ticket_code, 16) }}</div>
                                            </div>
                                            <x-order-status :status="$ticket->status" />
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
