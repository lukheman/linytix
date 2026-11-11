<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\RefundSucceeded;
use App\Services\Payment\FakeGateway;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;

/**
 * @return array{0: Order, 1: Payment, 2: Ticket}
 */
function refundOrder(array $orderOverrides = [], bool $withPayment = true): array
{
    $event = Event::factory()->create([
        'title' => 'Event Refund Nusa',
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(7),
    ]);

    $ticketType = TicketType::factory()->forEvent($event)->create([
        'name' => 'Reguler',
        'price' => 150000,
        'quota' => 10,
        'sold_count' => 1,
    ]);

    $order = Order::factory()->paid()->create(array_merge([
        'event_id' => $event->getKey(),
        'buyer_name' => 'Budi Pembeli',
        'buyer_email' => 'budi@example.com',
        'subtotal' => 150000,
        'total_amount' => 150000,
    ], $orderOverrides));

    $item = OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);

    $ticket = Ticket::factory()->create([
        'order_id' => $order->getKey(),
        'event_id' => $event->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'order_item_id' => $item->getKey(),
        'status' => Ticket::STATUS_ACTIVE,
    ]);

    $payment = $withPayment
        ? Payment::factory()->create([
            'order_id' => $order->getKey(),
            'gateway' => 'fake',
            'amount' => 150000,
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ])
        : null;

    return [$order, $payment, $ticket];
}

function refundAdmin(): User
{
    return User::factory()->admin()->create();
}

it('lets an admin refund a paid order fully', function () {
    Notification::fake();

    [$order, $payment, $ticket] = refundOrder();
    $admin = refundAdmin();

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order), ['reason' => 'Pembeli membatalkan kehadiran'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $refund = Refund::query()->firstOrFail();

    expect($refund->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and($refund->amount)->toBe(150000)
        ->and($refund->requested_by)->toBe($admin->getKey())
        ->and($refund->processed_by)->toBe($admin->getKey())
        ->and($refund->processed_at)->not->toBeNull()
        ->and($refund->gateway_ref)->toStartWith('FAKE-REFUND-')
        ->and($order->fresh()->status)->toBe(Order::STATUS_REFUNDED)
        ->and($ticket->fresh()->status)->toBe(Ticket::STATUS_REFUNDED)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_REFUNDED);

    expect(AuditLog::query()->where('action', 'refund.requested')->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'refund.succeeded')->count())->toBe(1);

    Notification::assertSentOnDemand(
        RefundSucceeded::class,
        function (RefundSucceeded $notification, array $channels, object $notifiable) use ($order): bool {
            return $notifiable->routes['mail'] === $order->buyer_email;
        },
    );
});

it('reuses the existing refund when an admin clicks refund twice', function () {
    Notification::fake();

    [$order] = refundOrder();
    $admin = refundAdmin();

    $this->actingAs($admin)->post(route('admin.orders.refund', $order), ['reason' => 'Refund pertama']);
    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order), ['reason' => 'Refund kedua'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Refund::query()->count())->toBe(1)
        ->and(Refund::query()->firstOrFail()->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and(AuditLog::query()->where('action', 'refund.requested')->count())->toBe(1);
});

it('marks a refund as failed and keeps the order paid when the gateway fails', function () {
    [$order, $payment, $ticket] = refundOrder();
    $admin = refundAdmin();

    $this->mock(FakeGateway::class, function (MockInterface $mock): void {
        $mock->shouldReceive('refund')->once()->andThrow(new RuntimeException('Gateway sedang gangguan'));
    });

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order), ['reason' => 'Event dibatalkan'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $refund = Refund::query()->firstOrFail();

    expect($refund->status)->toBe(Refund::STATUS_FAILED)
        ->and($refund->error)->toContain('Gateway sedang gangguan')
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($ticket->fresh()->status)->toBe(Ticket::STATUS_ACTIVE)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS)
        ->and(AuditLog::query()->where('action', 'refund.failed')->count())->toBe(1);
});

it('lets an admin retry a failed refund', function () {
    Notification::fake();

    [$order] = refundOrder();
    $admin = refundAdmin();

    $refund = Refund::query()->create([
        'order_id' => $order->getKey(),
        'payment_id' => $order->payments()->firstOrFail()->getKey(),
        'amount' => 150000,
        'reason' => 'Percobaan pertama gagal',
        'status' => Refund::STATUS_FAILED,
        'error' => 'Timeout',
        'requested_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.refunds.retry', $refund))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($refund->fresh()->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and($refund->fresh()->error)->toBeNull()
        ->and($order->fresh()->status)->toBe(Order::STATUS_REFUNDED)
        ->and(AuditLog::query()->where('action', 'refund.retried')->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'refund.succeeded')->count())->toBe(1);
});

it('cancels an event and refunds all paid orders in batch', function () {
    Notification::fake();

    [$paidOne] = refundOrder();
    [$paidTwo] = refundOrder(['event_id' => $paidOne->event_id]);
    [$pending] = refundOrder(['event_id' => $paidOne->event_id, 'status' => Order::STATUS_PENDING, 'paid_at' => null]);

    $admin = refundAdmin();
    $event = $paidOne->event;

    $this->actingAs($admin)
        ->post(route('admin.events.cancel'), [
            'event_id' => $event->getKey(),
            'reason' => 'Force majeure di lokasi',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->status)->toBe(Event::STATUS_CANCELLED)
        ->and(Refund::query()->where('status', Refund::STATUS_SUCCEEDED)->count())->toBe(2)
        ->and($paidOne->fresh()->status)->toBe(Order::STATUS_REFUNDED)
        ->and($paidTwo->fresh()->status)->toBe(Order::STATUS_REFUNDED)
        ->and($pending->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and(AuditLog::query()->where('action', 'event.cancelled')->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'refund.batch_requested')->count())->toBe(1);
});

it('requires a reason when requesting a refund', function () {
    [$order] = refundOrder();
    $admin = refundAdmin();

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect(Refund::query()->count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID);
});

it('rejects refunds for orders that are not paid', function () {
    [$order] = refundOrder(['status' => Order::STATUS_PENDING, 'paid_at' => null]);
    $admin = refundAdmin();

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order), ['reason' => 'Pembeli ingin refund'])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Refund::query()->count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

it('shows the refund management page to admins', function () {
    [$order] = refundOrder();
    $admin = refundAdmin();

    Refund::query()->create([
        'order_id' => $order->getKey(),
        'payment_id' => $order->payments()->firstOrFail()->getKey(),
        'amount' => 150000,
        'reason' => 'Menunggu diproses',
        'status' => Refund::STATUS_REQUESTED,
        'requested_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.refunds'))
        ->assertOk()
        ->assertSee('Daftar Refund')
        ->assertSee($order->order_number)
        ->assertSee('Batalkan Event')
        ->assertSee($order->event->title);
});

it('prevents non admins from accessing refund actions', function () {
    [$order] = refundOrder();
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get(route('admin.refunds'))->assertForbidden();

    $this->actingAs($customer)
        ->post(route('admin.orders.refund', $order), ['reason' => 'Percobaan tidak sah'])
        ->assertForbidden();

    expect(Refund::query()->count())->toBe(0);
});

it('prevents retrying a refund that already succeeded', function () {
    [$order] = refundOrder();
    $admin = refundAdmin();

    $refund = Refund::query()->create([
        'order_id' => $order->getKey(),
        'payment_id' => $order->payments()->firstOrFail()->getKey(),
        'amount' => 150000,
        'reason' => 'Sudah selesai',
        'status' => Refund::STATUS_SUCCEEDED,
        'requested_by' => $admin->getKey(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.refunds.retry', $refund))
        ->assertForbidden();
});
