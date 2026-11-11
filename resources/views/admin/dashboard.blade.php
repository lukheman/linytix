<x-layouts.app title="Dashboard Ticketing - Admin">
    <x-layout.page-header title="Dashboard Ticketing" subtitle="Pantau event, pesanan, dan tiket dalam satu tempat.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('events.index') }}" target="_blank">
                <i class="fas fa-ticket me-2"></i>Lihat Event
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <x-layout.stat-card icon="fas fa-calendar-days" label="Total Event" :value="$stats['events']" variant="primary" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-layout.stat-card icon="fas fa-calendar-check" label="Event Terbit" :value="$stats['published_events']" variant="success" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-layout.stat-card icon="fas fa-receipt" label="Total Pesanan" :value="$stats['orders']" variant="secondary" />
        </div>
        <div class="col-md-6 col-lg-3">
            <x-layout.stat-card icon="fas fa-ticket" label="Total Tiket" :value="$stats['tickets']" variant="warning" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-layout.modern-card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Pesanan terbaru</h2>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('events.index') }}" target="_blank">Buka katalog event</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Nomor Pesanan</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><strong>{{ $order->order_number }}</strong></td>
                                    <td class="text-muted">{{ $order->event?->title ?? 'Event tidak tersedia' }}</td>
                                    <td><x-order-status :status="$order->status" /></td>
                                    <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-layout.modern-card>
        </div>

        <div class="col-lg-4">
            <x-layout.modern-card>
                <h2 class="h5 mb-3">Akses cepat</h2>
                <div class="d-flex flex-column gap-2">
                    <a class="btn btn-outline-primary text-start" href="{{ route('events.index') }}" target="_blank"><i class="fas fa-ticket me-2"></i>Jelajahi event</a>
                    <a class="btn btn-outline-primary text-start" href="{{ route('admin.settings') }}"><i class="fas fa-sliders me-2"></i>Pengaturan ticketing</a>
                    <a class="btn btn-outline-secondary text-start" href="{{ route('home') }}" target="_blank"><i class="fas fa-arrow-up-right-from-square me-2"></i>Lihat website</a>
                </div>
            </x-layout.modern-card>
        </div>
    </div>
</x-layouts.app>
