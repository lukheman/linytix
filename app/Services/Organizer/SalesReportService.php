<?php

namespace App\Services\Organizer;

use App\Models\Event;
use App\Models\Order;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class SalesReportService
{
    /**
     * Ringkasan penjualan event untuk rentang tanggal dan jenis tiket tertentu.
     *
     * Hanya order berstatus `paid` yang dihitung. Refund sukses ditampilkan
     * terpisah dan dikurangkan pada pendapatan bersih.
     *
     * @param  array{from?: ?string, to?: ?string, ticket_type_id?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function forEvent(Event $event, array $filters = []): array
    {
        $timezone = $event->timezoneLabel();

        $from = $this->resolveDate($filters['from'] ?? null, $timezone)
            ?? Carbon::now($timezone)->subDays(29)->startOfDay();
        $to = $this->resolveDate($filters['to'] ?? null, $timezone)
            ?? Carbon::now($timezone)->startOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $startUtc = $from->copy()->startOfDay()->utc();
        $endUtc = $to->copy()->endOfDay()->utc();

        $ticketTypeId = $filters['ticket_type_id'] ?? null;

        $itemRows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('ticket_types', 'ticket_types.id', '=', 'order_items.ticket_type_id')
            ->where('orders.event_id', $event->getKey())
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$startUtc, $endUtc])
            ->when($ticketTypeId, fn ($query) => $query->where('order_items.ticket_type_id', $ticketTypeId))
            ->select([
                'orders.id as order_id',
                'orders.paid_at',
                'order_items.ticket_type_id',
                'order_items.quantity',
                'order_items.subtotal',
                'ticket_types.name as ticket_type_name',
            ])
            ->get();

        $refundRows = DB::table('refunds')
            ->join('orders', 'orders.id', '=', 'refunds.order_id')
            ->where('orders.event_id', $event->getKey())
            ->where('refunds.status', Refund::STATUS_SUCCEEDED)
            ->whereBetween('refunds.processed_at', [$startUtc, $endUtc])
            ->when($ticketTypeId, fn ($query) => $query->whereIn(
                'refunds.order_id',
                DB::table('order_items')
                    ->select('order_id')
                    ->where('ticket_type_id', $ticketTypeId)
            ))
            ->select(['refunds.amount', 'refunds.processed_at'])
            ->get();

        $daily = [];

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();

            $daily[$key] = [
                'date' => $key,
                'label' => $day->copy()->translatedFormat('d M'),
                'revenue' => 0,
                'tickets' => 0,
                'refunds' => 0,
            ];
        }

        $summary = [
            'orders' => 0,
            'tickets_sold' => 0,
            'revenue' => 0,
            'refunds' => 0,
            'net_revenue' => 0,
        ];

        $orderIds = [];
        $byTicketType = [];

        foreach ($itemRows as $row) {
            $quantity = (int) $row->quantity;
            $subtotal = (int) $row->subtotal;

            $summary['tickets_sold'] += $quantity;
            $summary['revenue'] += $subtotal;
            $orderIds[$row->order_id] = true;

            $localDate = Carbon::parse($row->paid_at, 'UTC')->setTimezone($timezone)->toDateString();

            if (isset($daily[$localDate])) {
                $daily[$localDate]['revenue'] += $subtotal;
                $daily[$localDate]['tickets'] += $quantity;
            }

            $typeKey = $row->ticket_type_id ?? 'unknown';

            if (! isset($byTicketType[$typeKey])) {
                $byTicketType[$typeKey] = [
                    'ticket_type_id' => $row->ticket_type_id,
                    'name' => $row->ticket_type_name ?? 'Tidak diketahui',
                    'tickets' => 0,
                    'revenue' => 0,
                ];
            }

            $byTicketType[$typeKey]['tickets'] += $quantity;
            $byTicketType[$typeKey]['revenue'] += $subtotal;
        }

        foreach ($refundRows as $row) {
            $amount = (int) $row->amount;

            $summary['refunds'] += $amount;

            $localDate = Carbon::parse($row->processed_at, 'UTC')->setTimezone($timezone)->toDateString();

            if (isset($daily[$localDate])) {
                $daily[$localDate]['refunds'] += $amount;
            }
        }

        $summary['orders'] = count($orderIds);
        $summary['net_revenue'] = $summary['revenue'] - $summary['refunds'];

        usort($byTicketType, fn (array $a, array $b) => $b['revenue'] <=> $a['revenue']);

        return [
            'event' => $event,
            'timezone' => $timezone,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'ticket_type_id' => $ticketTypeId,
            ],
            'summary' => $summary,
            'daily' => array_values($daily),
            'by_ticket_type' => array_values($byTicketType),
        ];
    }

    private function resolveDate(?string $value, string $timezone): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value, $timezone)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
