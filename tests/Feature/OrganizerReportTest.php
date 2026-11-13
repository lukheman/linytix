<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Refund;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use App\Services\Organizer\SalesReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @return array{0: User, 1: Organizer, 2: Event, 3: TicketType, 4: TicketType}
 */
function reportContext(array $eventOverrides = []): array
{
    $user = User::factory()->organizer()->create();

    $organizer = Organizer::factory()
        ->forOwner($user)
        ->create([
            'name' => 'Nusa Report',
            'slug' => 'nusa-report',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

    $venue = Venue::factory()->create(['capacity' => 1000]);

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->create(array_merge([
            'venue_id' => $venue->getKey(),
            'timezone' => 'Asia/Jakarta',
            'start_at' => Carbon::parse('2026-12-31 19:00', 'Asia/Jakarta')->utc(),
            'end_at' => Carbon::parse('2026-12-31 22:00', 'Asia/Jakarta')->utc(),
        ], $eventOverrides));

    $regular = TicketType::factory()->forEvent($event)->create([
        'name' => 'Reguler',
        'price' => 100000,
    ]);

    $vip = TicketType::factory()->forEvent($event)->create([
        'name' => 'VIP',
        'price' => 250000,
    ]);

    return [$user, $organizer, $event, $regular, $vip];
}

function reportOrder(
    Event $event,
    TicketType $ticketType,
    int $quantity = 2,
    ?Carbon $paidAt = null,
    array $overrides = [],
): Order {
    $subtotal = $ticketType->price * $quantity;

    $order = Order::factory()->paid()->create(array_merge([
        'event_id' => $event->getKey(),
        'subtotal' => $subtotal,
        'total_amount' => $subtotal,
        'paid_at' => ($paidAt ?? now())->copy()->utc(),
    ], $overrides));

    OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => $quantity,
        'unit_price' => $ticketType->price,
        'subtotal' => $subtotal,
    ]);

    return $order;
}

function reportService(): SalesReportService
{
    return app(SalesReportService::class);
}

it('calculates paid revenue and tickets while excluding other order statuses', function () {
    [, , $event, $regular, $vip] = reportContext();

    reportOrder($event, $regular, 2, Carbon::parse('2026-11-05 10:00', 'Asia/Jakarta'));
    reportOrder($event, $vip, 1, Carbon::parse('2026-11-06 10:00', 'Asia/Jakarta'));

    reportOrder($event, $regular, 3, Carbon::parse('2026-11-07 10:00', 'Asia/Jakarta'), [
        'status' => Order::STATUS_PENDING,
    ]);
    reportOrder($event, $regular, 3, Carbon::parse('2026-11-07 10:00', 'Asia/Jakarta'), [
        'status' => Order::STATUS_EXPIRED,
    ]);
    reportOrder($event, $regular, 3, Carbon::parse('2026-11-07 10:00', 'Asia/Jakarta'), [
        'status' => Order::STATUS_CANCELLED,
    ]);

    $refundedOrder = reportOrder($event, $vip, 2, Carbon::parse('2026-11-08 10:00', 'Asia/Jakarta'), [
        'status' => Order::STATUS_REFUNDED,
    ]);

    Refund::query()->create([
        'order_id' => $refundedOrder->getKey(),
        'amount' => 500000,
        'reason' => 'Event berubah',
        'status' => Refund::STATUS_SUCCEEDED,
        'processed_at' => Carbon::parse('2026-11-09 09:00', 'Asia/Jakarta')->utc(),
    ]);

    $report = reportService()->forEvent($event, [
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]);

    expect($report['summary']['orders'])->toBe(2)
        ->and($report['summary']['tickets_sold'])->toBe(3)
        ->and($report['summary']['revenue'])->toBe(450000)
        ->and($report['summary']['refunds'])->toBe(500000)
        ->and($report['summary']['net_revenue'])->toBe(-50000);
});

it('defaults to the last 30 days and filters by date range', function () {
    [, , $event, $regular] = reportContext();

    $today = Carbon::now('Asia/Jakarta')->startOfDay();

    reportOrder($event, $regular, 1, $today->copy()->subDays(5));
    reportOrder($event, $regular, 1, $today->copy()->subDays(40));

    $default = reportService()->forEvent($event);

    expect($default['filters']['to'])->toBe($today->toDateString())
        ->and($default['filters']['from'])->toBe($today->copy()->subDays(29)->toDateString())
        ->and($default['summary']['revenue'])->toBe(100000);

    $custom = reportService()->forEvent($event, [
        'from' => $today->copy()->subDays(50)->toDateString(),
        'to' => $today->copy()->subDays(35)->toDateString(),
    ]);

    expect($custom['summary']['revenue'])->toBe(100000);
});

it('filters revenue and ticket counts by ticket type', function () {
    [, , $event, $regular, $vip] = reportContext();

    reportOrder($event, $regular, 2, Carbon::parse('2026-11-05 10:00', 'Asia/Jakarta'));
    reportOrder($event, $vip, 1, Carbon::parse('2026-11-06 10:00', 'Asia/Jakarta'));

    $report = reportService()->forEvent($event, [
        'from' => '2026-11-01',
        'to' => '2026-11-30',
        'ticket_type_id' => $vip->getKey(),
    ]);

    expect($report['summary']['tickets_sold'])->toBe(1)
        ->and($report['summary']['revenue'])->toBe(250000)
        ->and($report['by_ticket_type'])->toHaveCount(1)
        ->and($report['by_ticket_type'][0]['name'])->toBe('VIP');
});

it('groups daily totals using the event timezone', function () {
    [, , $event, $regular] = reportContext();

    // 18:00 UTC = 01:00 WIB tanggal berikutnya.
    reportOrder($event, $regular, 1, Carbon::parse('2026-11-01 18:00', 'UTC'));

    $report = reportService()->forEvent($event, [
        'from' => '2026-11-01',
        'to' => '2026-11-03',
    ]);

    $days = collect($report['daily'])->keyBy('date');

    expect($days['2026-11-01']['revenue'])->toBe(0)
        ->and($days['2026-11-02']['revenue'])->toBe(100000)
        ->and($days['2026-11-02']['tickets'])->toBe(1);
});

it('shows the report page only to the event owner', function () {
    [$user, $organizer, $event, $regular] = reportContext();

    reportOrder($event, $regular, 1, Carbon::parse('2026-11-05 10:00', 'Asia/Jakarta'));

    $this->actingAs($user)
        ->get(route('organizer.events.report', $event))
        ->assertOk()
        ->assertSee('Laporan Penjualan')
        ->assertSee('Reguler');

    $other = User::factory()->organizer()->create();
    Organizer::factory()->forOwner($other)->create([
        'slug' => 'organizer-lain',
        'is_verified' => true,
    ]);

    $this->actingAs($other)
        ->get(route('organizer.events.report', $event))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('organizer.events.report', $event).'?ticket_type_id='.Str::uuid())
        ->assertNotFound();
});

it('generates a report for ten thousand orders within the performance budget', function () {
    [, , $event, $regular] = reportContext();

    $paidAt = Carbon::parse('2026-11-15 12:00', 'Asia/Jakarta')->utc();
    $timestamp = now();
    $orderRows = [];
    $itemRows = [];

    for ($index = 0; $index < 10000; $index++) {
        $orderId = (string) Str::uuid();

        $orderRows[] = [
            'id' => $orderId,
            'order_number' => 'ORD-PERF-'.$index,
            'user_id' => null,
            'event_id' => $event->getKey(),
            'voucher_id' => null,
            'buyer_name' => 'Pembeli '.$index,
            'buyer_email' => 'pembeli'.$index.'@example.test',
            'buyer_phone' => null,
            'holder_names' => null,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'admin_fee' => 0,
            'payment_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => 100000,
            'status' => Order::STATUS_PAID,
            'expires_at' => null,
            'paid_at' => $paidAt,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        $itemRows[] = [
            'id' => (string) Str::uuid(),
            'order_id' => $orderId,
            'ticket_type_id' => $regular->getKey(),
            'quantity' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    foreach (array_chunk($orderRows, 500) as $chunk) {
        DB::table('orders')->insert($chunk);
    }

    foreach (array_chunk($itemRows, 500) as $chunk) {
        DB::table('order_items')->insert($chunk);
    }

    $startedAt = microtime(true);

    $report = reportService()->forEvent($event, [
        'from' => '2026-11-01',
        'to' => '2026-11-30',
    ]);

    $durationMs = (microtime(true) - $startedAt) * 1000;

    expect($report['summary']['orders'])->toBe(10000)
        ->and($report['summary']['tickets_sold'])->toBe(10000)
        ->and($durationMs)->toBeLessThan(500);
});
