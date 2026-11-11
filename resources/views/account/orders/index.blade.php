<x-layouts.public
    :settings="$settings"
    :title="'Pesanan Saya — '.($settings['site_name'] ?? 'LinyTix')"
    :canonical="route('akun.orders.index')"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <div class="eyebrow">Akun</div>
                    <h1 class="display-6 mb-0">Riwayat pesanan</h1>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('akun.dashboard') }}" class="btn btn-ghost">Akun</a>
                    <a href="{{ route('akun.tickets.index') }}" class="btn btn-main">Tiket saya</a>
                </div>
            </div>

            @if ($orders->isEmpty())
                <div class="card-clean p-5 text-center">
                    <div class="icon-box mx-auto mb-3"><i class="fa-solid fa-receipt"></i></div>
                    <h2 class="h5">Belum ada pesanan</h2>
                    <p class="text-secondary mb-3">Pesanan yang Anda buat akan muncul di sini.</p>
                    <a href="{{ route('events.index') }}" class="btn btn-main">Jelajahi event</a>
                </div>
            @else
                <div class="card-clean overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nomor order</th>
                                    <th scope="col">Event</th>
                                    <th scope="col">Tanggal</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td class="fw-semibold text-nowrap">{{ $order->order_number }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $order->event?->title ?? 'Event' }}</div>
                                            <div class="small text-secondary">{{ $order->event?->startsAtLocal()?->translatedFormat('d M Y, H.i') }}</div>
                                        </td>
                                        <td class="text-nowrap">{{ $order->created_at?->translatedFormat('d M Y') }}</td>
                                        <td class="text-end text-nowrap">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</td>
                                        <td><x-order-status :status="$order->status" /></td>
                                        <td class="text-end">
                                            <a href="{{ route('akun.orders.show', $order) }}" class="btn btn-sm btn-ghost">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
