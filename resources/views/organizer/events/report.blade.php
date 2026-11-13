<x-layouts.app title="Laporan Penjualan - Nusa Event" brand-name="Nusa Event" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Laporan Penjualan" subtitle="{{ $event->title }}">
        <x-slot:actions>
            <a href="{{ route('organizer.events.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Event
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    @php
        $summary = $report['summary'];
        $filters = $report['filters'];
        $currency = fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
    @endphp

    <x-layout.modern-card class="mb-4">
        <form method="GET" action="{{ route('organizer.events.report', $event) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="from" class="form-label small fw-semibold">Dari tanggal</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="to" class="form-label small fw-semibold">Sampai tanggal</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="ticket_type_id" class="form-label small fw-semibold">Jenis tiket</label>
                <select id="ticket_type_id" name="ticket_type_id" class="form-select">
                    <option value="">Semua jenis tiket</option>
                    @foreach ($ticketTypes as $ticketType)
                        <option value="{{ $ticketType->getKey() }}" @selected($filters['ticket_type_id'] === $ticketType->getKey())>
                            {{ $ticketType->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Terapkan
                </button>
                <a href="{{ route('organizer.events.report', $event) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="small text-muted mt-3">
            Zona waktu {{ $report['timezone'] }} · hanya order <strong>paid</strong> yang dihitung ·
            default 30 hari terakhir.
        </div>
    </x-layout.modern-card>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Tiket Terjual', 'value' => number_format($summary['tickets_sold'], 0, ',', '.'), 'note' => $summary['orders'].' order paid', 'icon' => 'fas fa-ticket', 'variant' => 'primary'],
                ['label' => 'Pendapatan', 'value' => $currency($summary['revenue']), 'note' => 'Sum subtotal order paid', 'icon' => 'fas fa-wallet', 'variant' => 'success'],
                ['label' => 'Refund', 'value' => $currency($summary['refunds']), 'note' => 'Refund sukses, terpisah', 'icon' => 'fas fa-rotate-left', 'variant' => 'warning'],
                ['label' => 'Pendapatan Bersih', 'value' => $currency($summary['net_revenue']), 'note' => 'Pendapatan − refund', 'icon' => 'fas fa-chart-line', 'variant' => $summary['net_revenue'] < 0 ? 'danger' : 'info'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-6 col-xl-3">
                <div class="modern-card h-100">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h4 mb-1 fw-bold">{{ $card['value'] }}</div>
                            <div class="small text-muted">{{ $card['note'] }}</div>
                        </div>
                        <x-ui.badge :variant="$card['variant']"><i class="{{ $card['icon'] }}"></i></x-ui.badge>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <x-layout.modern-card title="Grafik Harian">
                <canvas id="sales-chart" height="150"></canvas>
                @if (collect($report['daily'])->sum('revenue') === 0 && collect($report['daily'])->sum('refunds') === 0)
                    <p class="text-muted small mb-0 mt-3 text-center">Belum ada transaksi pada rentang tanggal ini.</p>
                @endif
            </x-layout.modern-card>
        </div>

        <div class="col-xl-4">
            <x-layout.modern-card title="Rincian per Jenis Tiket">
                @forelse ($report['by_ticket_type'] as $row)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="fw-semibold">{{ $row['name'] }}</div>
                            <div class="small text-muted text-nowrap">{{ number_format($row['tickets'], 0, ',', '.') }} tiket</div>
                        </div>
                        <div class="small text-secondary">{{ $currency((int) $row['revenue']) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Belum ada tiket terjual pada rentang ini.</p>
                @endforelse
            </x-layout.modern-card>

            <x-layout.modern-card title="Catatan" class="mt-4">
                <ul class="small text-secondary mb-0">
                    <li>Order <code>pending</code>, <code>expired</code>, <code>cancelled</code>, dan <code>refunded</code> tidak dihitung sebagai pendapatan.</li>
                    <li>Pendapatan memakai snapshot harga pada <code>order_items</code>, sehingga perubahan harga tiket tidak mengubah laporan lama.</li>
                    <li>Tanggal mengikuti timezone event ({{ $report['timezone'] }}).</li>
                </ul>
            </x-layout.modern-card>
        </div>
    </div>

    <x-layout.modern-card title="Rincian Harian" class="mt-4">
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-end">Tiket</th>
                        <th class="text-end">Pendapatan</th>
                        <th class="text-end">Refund</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (array_reverse($report['daily']) as $day)
                        <tr>
                            <td class="small">{{ \Carbon\Carbon::parse($day['date'])->translatedFormat('d M Y') }}</td>
                            <td class="text-end small">{{ number_format($day['tickets'], 0, ',', '.') }}</td>
                            <td class="text-end small">{{ $currency((int) $day['revenue']) }}</td>
                            <td class="text-end small">{{ $currency((int) $day['refunds']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-layout.modern-card>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const canvas = document.getElementById('sales-chart');

                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                const labels = @json(array_column($report['daily'], 'label'));
                const revenue = @json(array_column($report['daily'], 'revenue'));
                const refunds = @json(array_column($report['daily'], 'refunds'));

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Pendapatan',
                                data: revenue,
                                backgroundColor: 'rgba(25, 135, 84, 0.65)',
                                borderColor: 'rgba(25, 135, 84, 1)',
                                borderWidth: 1,
                            },
                            {
                                label: 'Refund',
                                data: refunds,
                                backgroundColor: 'rgba(255, 193, 7, 0.65)',
                                borderColor: 'rgba(255, 193, 7, 1)',
                                borderWidth: 1,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.dataset.label + ': Rp ' + Number(context.raw).toLocaleString('id-ID');
                                    },
                                },
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return 'Rp ' + Number(value).toLocaleString('id-ID');
                                    },
                                },
                            },
                        },
                    },
                });
            })();
        </script>
    @endpush
</x-layouts.app>
