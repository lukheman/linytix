<?php

use App\Jobs\SendTicketEmail;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Payment\FakeGateway;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

/**
 * @return array{0: Order, 1: Payment, 2: Ticket, 3: Event}
 */
function monitoringOrder(array $orderOverrides = [], array $paymentOverrides = [], bool $withTicket = true): array
{
    $event = Event::factory()->create([
        'title' => 'Event Monitoring '.fake()->unique()->word(),
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(7),
    ]);

    $ticketType = TicketType::factory()->forEvent($event)->create([
        'name' => 'Reguler Monitoring',
        'price' => 150000,
        'quota' => 10,
        'sold_count' => 1,
    ]);

    $order = Order::factory()->create(array_merge([
        'event_id' => $event->getKey(),
        'buyer_name' => 'Budi Monitoring',
        'buyer_email' => 'budi.monitoring@example.com',
        'buyer_phone' => '081234567890',
        'subtotal' => 150000,
        'total_amount' => 150000,
        'status' => Order::STATUS_PAID,
        'paid_at' => now(),
    ], $orderOverrides));

    $item = OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);

    $ticket = $withTicket
        ? Ticket::factory()->create([
            'order_id' => $order->getKey(),
            'event_id' => $event->getKey(),
            'ticket_type_id' => $ticketType->getKey(),
            'order_item_id' => $item->getKey(),
            'holder_name' => 'Budi Monitoring',
            'holder_id_number' => '3171234567890001',
            'status' => Ticket::STATUS_ACTIVE,
        ])
        : null;

    $payment = Payment::factory()->create(array_merge([
        'order_id' => $order->getKey(),
        'gateway' => 'fake',
        'method' => 'qris',
        'amount' => 150000,
        'status' => Payment::STATUS_SUCCESS,
        'paid_at' => now(),
    ], $paymentOverrides));

    return [$order, $payment, $ticket, $event];
}

it('filters admin orders by status event date and search', function () {
    $admin = User::factory()->admin()->create();

    [$paidOrder, , , $paidEvent] = monitoringOrder();
    [$pendingOrder, , , $pendingEvent] = monitoringOrder([
        'buyer_email' => 'pending.monitoring@example.com',
        'status' => Order::STATUS_PENDING,
        'paid_at' => null,
    ]);

    $paidEvent->forceFill(['title' => 'Event Filter Alpha'])->save();
    $pendingEvent->forceFill(['title' => 'Event Filter Beta'])->save();

    $paidOrder->forceFill(['created_at' => now()->subDays(10)])->save();
    $pendingOrder->forceFill(['created_at' => now()])->save();

    $this->actingAs($admin)
        ->get(route('admin.orders', ['status' => Order::STATUS_PAID]))
        ->assertOk()
        ->assertSee($paidOrder->order_number)
        ->assertDontSee($pendingOrder->order_number);

    $this->actingAs($admin)
        ->get(route('admin.orders', ['event_id' => $paidEvent->getKey()]))
        ->assertOk()
        ->assertSee($paidOrder->order_number)
        ->assertDontSee($pendingOrder->order_number);

    $this->actingAs($admin)
        ->get(route('admin.orders', [
            'date_from' => now()->subDays(12)->toDateString(),
            'date_to' => now()->subDays(8)->toDateString(),
        ]))
        ->assertOk()
        ->assertSee($paidOrder->order_number)
        ->assertDontSee($pendingOrder->order_number);

    $this->actingAs($admin)
        ->get(route('admin.orders', ['q' => $paidOrder->order_number]))
        ->assertOk()
        ->assertSee($paidOrder->order_number)
        ->assertDontSee($pendingOrder->order_number);

    $this->actingAs($admin)
        ->get(route('admin.orders', ['q' => 'pending.monitoring@example.com']))
        ->assertOk()
        ->assertSee($pendingOrder->order_number)
        ->assertDontSee($paidOrder->order_number);
});

it('shows order detail with items payments tickets refunds and masked identity', function () {
    [$order, $payment, $ticket] = monitoringOrder();
    $admin = User::factory()->admin()->create();

    Refund::query()->create([
        'order_id' => $order->getKey(),
        'payment_id' => $payment->getKey(),
        'amount' => 150000,
        'reason' => 'Gateway menolak refund pertama',
        'status' => Refund::STATUS_FAILED,
        'error' => 'Timeout gateway',
        'requested_by' => $admin->getKey(),
    ]);

    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'gateway' => 'fake',
        'method' => 'va_bca',
        'amount' => 150000,
        'status' => Payment::STATUS_FAILED,
        'paid_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee($order->event->title)
        ->assertSee('Reguler Monitoring')
        ->assertSee('qris')
        ->assertSee('va_bca')
        ->assertSee('Failed')
        ->assertSee('Rekonsiliasi')
        ->assertSee('Gateway menolak refund pertama')
        ->assertSee('Timeout gateway')
        ->assertSee('****0001')
        ->assertSee('****7890')
        ->assertDontSee('3171234567890001')
        ->assertDontSee('081234567890')
        ->assertDontSee($ticket->holder_id_number);
});

it('lets an admin create a full refund from order monitoring', function () {
    Notification::fake();

    [$order] = monitoringOrder();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order), ['reason' => 'Pembeli membatalkan kehadiran'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Refund::query()->firstOrFail()->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and($order->fresh()->status)->toBe(Order::STATUS_REFUNDED)
        ->and(AuditLog::query()->where('action', 'refund.requested')->count())->toBe(1);
});

it('lets an admin manually reconcile a failed payment', function () {
    Queue::fake();

    [$order, $payment] = monitoringOrder(
        ['status' => Order::STATUS_PENDING, 'paid_at' => null],
        ['status' => Payment::STATUS_FAILED, 'paid_at' => null],
    );
    $admin = User::factory()->admin()->create();

    $this->mock(FakeGateway::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getStatus')->once()->andReturn(Payment::STATUS_SUCCESS);
    });

    $this->actingAs($admin)
        ->post(route('admin.orders.payments.reconcile', [$order, $payment]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and(AuditLog::query()->where('action', 'payment.reconciled')->where('entity', 'payment')->exists())->toBeTrue();
});

it('updates a payment status when manual reconciliation finds it expired', function () {
    Queue::fake();

    [$order, $payment] = monitoringOrder(
        ['status' => Order::STATUS_PENDING, 'paid_at' => null],
        ['status' => Payment::STATUS_PENDING, 'paid_at' => null],
    );
    $admin = User::factory()->admin()->create();

    $this->mock(FakeGateway::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getStatus')->once()->andReturn(Payment::STATUS_EXPIRED);
    });

    $this->actingAs($admin)
        ->post(route('admin.orders.payments.reconcile', [$order, $payment]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_EXPIRED)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and(AuditLog::query()->where('action', 'payment.reconciled')->count())->toBe(1);
});

it('rejects reconciling a payment that belongs to another order', function () {
    [$order] = monitoringOrder();
    [, $otherPayment] = monitoringOrder();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.orders.payments.reconcile', [$order, $otherPayment]))
        ->assertNotFound();
});

it('audits resending the e-ticket', function () {
    Queue::fake();

    [$order] = monitoringOrder();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.orders.resend-ticket', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(SendTicketEmail::class);
    expect(AuditLog::query()->where('action', 'order.ticket_resent')->where('entity', 'order')->exists())->toBeTrue();
});

it('restricts order monitoring to admins', function () {
    [$order, $payment] = monitoringOrder();
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get(route('admin.orders'))->assertForbidden();
    $this->actingAs($customer)->get(route('admin.orders.show', $order))->assertForbidden();
    $this->actingAs($customer)
        ->post(route('admin.orders.payments.reconcile', [$order, $payment]))
        ->assertForbidden();
    $this->actingAs($customer)
        ->post(route('admin.orders.resend-ticket', $order))
        ->assertForbidden();

    expect(AuditLog::query()->where('action', 'payment.reconciled')->count())->toBe(0)
        ->and(AuditLog::query()->where('action', 'order.ticket_resent')->count())->toBe(0);
});
