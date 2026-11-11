@php
    $verified = $user->hasVerifiedEmail();
@endphp

<x-layouts.public
    :settings="$settings"
    :title="'Akun Saya — '.($settings['site_name'] ?? 'LinyTix')"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <div class="eyebrow">Akun</div>
                    <h1 class="display-6 mb-1">Halo, {{ $user->name }}.</h1>
                    <p class="text-secondary mb-0">{{ $user->email }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('akun.orders.index') }}" class="btn btn-main">Pesanan saya</a>
                    <a href="{{ route('akun.tickets.index') }}" class="btn btn-ghost">Tiket saya</a>
                </div>
            </div>

            @unless ($verified)
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong>Email belum terverifikasi.</strong>
                        Verifikasi email diperlukan sebelum checkout sebagai customer.
                    </div>
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill">Kirim ulang verifikasi</button>
                    </form>
                </div>
            @endunless

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            <div class="row g-4 mb-5">
                <div class="col-sm-6 col-lg-3">
                    <div class="card-clean p-4">
                        <div class="small text-secondary">Total pesanan</div>
                        <div class="display-6 text-success">{{ $orderCount }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-clean p-4">
                        <div class="small text-secondary">Tiket aktif</div>
                        <div class="display-6 text-success">{{ $ticketCount }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-clean p-4">
                        <div class="small text-secondary">Status email</div>
                        <div class="h4 mt-2 mb-0">
                            @if ($verified)
                                <span class="badge text-bg-success">Terverifikasi</span>
                            @else
                                <span class="badge text-bg-warning">Belum verifikasi</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-clean p-4">
                        <div class="small text-secondary">Cari pesanan guest</div>
                        <a href="{{ route('guest.orders.lookup') }}" class="text-link d-inline-block mt-2">Buka pencarian →</a>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <div class="eyebrow">Mendatang</div>
                    <h2 class="h3 mb-0">Event Anda</h2>
                </div>
            </div>

            @if ($upcoming->isEmpty())
                <div class="card-clean p-4 text-secondary">
                    Belum ada event mendatang. <a href="{{ route('events.index') }}" class="text-link">Jelajahi event</a>.
                </div>
            @else
                <div class="row g-4">
                    @foreach ($upcoming as $order)
                        <div class="col-md-6 col-lg-4">
                            <div class="card-clean p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="small text-secondary">{{ $order->order_number }}</div>
                                    <x-order-status :status="$order->status" />
                                </div>
                                <h3 class="h5">{{ $order->event?->title ?? 'Event' }}</h3>
                                <div class="small text-secondary mb-3">
                                    <i class="fa-regular fa-calendar me-2"></i>
                                    {{ $order->event?->startsAtLocal()?->translatedFormat('d M Y, H.i') }}
                                </div>
                                <a href="{{ route('akun.orders.show', $order) }}" class="btn btn-ghost btn-sm mt-auto align-self-start">Lihat pesanan</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
