<?php

use App\Jobs\ExpireOrders;
use App\Jobs\IssueTickets;
use App\Jobs\ReconcilePayments;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\WebhookEvent;
use App\Notifications\OrderExpired;
use App\Notifications\OrderRefundRequested;
use App\Notifications\TicketIssued;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentReconciliationService;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;

function webhookOrder(array $orderOverrides = [], array $typeOverrides = []): array
{
    $event = Event::factory()->create([
        'title' => 'Event Webhook',
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(7),
    ]);

    $ticketType = TicketType::factory()->forEvent($event)->create(array_merge([
        'name' => 'Reguler',
        'price' => 150000,
        'quota' => 2,
        'sold_count' => 1,
        'is_active' => true,
    ], $typeOverrides));

    $order = Order::factory()->create(array_merge([
        'event_id' => $event->getKey(),
        'buyer_name' => 'Budi Pembeli',
        'buyer_email' => 'budi@example.com',
        'subtotal' => 150000,
        'total_amount' => 150000,
        'status' => Order::STATUS_PENDING,
        'expires_at' => now()->addMinutes(15),
    ], $orderOverrides));

    $order->items()->create([
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);

    $payment = $order->payments()->create([
        'gateway' => 'fake',
        'method' => 'qris',
        'gateway_ref' => 'FAKE-'.strtoupper(str()->random(10)),
        'amount' => (int) $order->total_amount,
        'status' => Payment::STATUS_PENDING,
    ]);

    return [$order, $ticketType, $payment];
}

function webhookPayload(Order $order, string $externalId, string $status = 'success', ?int $amount = null): array
{
    return [
        'external_id' => $externalId,
        'event_type' => 'payment',
        'status' => $status,
        'order_number' => $order->order_number,
        'amount' => $amount ?? (int) $order->total_amount,
    ];
}

function postFakeWebhook(TestCase $test, array $payload, ?string $signature = null)
{
    $json = json_encode($payload);
    $signature ??= hash_hmac('sha256', $json, (string) config('payment.fake.secret', 'fake-secret'));

    return $test->call(
        'POST',
        route('webhooks.payment', 'fake'),
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X_FAKE_SIGNATURE' => $signature],
        $json,
    );
}

it('rejects webhooks with an invalid signature', function () {
    [$order] = webhookOrder();

    postFakeWebhook($this, webhookPayload($order, 'EVT-INVALID'), 'invalid')
        ->assertForbidden();

    $event = WebhookEvent::query()->firstOrFail();

    expect($event->signature_valid)->toBeFalse()
        ->and($event->status)->toBe(WebhookEvent::STATUS_INVALID_SIGNATURE)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

it('marks a pending order paid and issues tickets from a valid webhook', function () {
    [$order, $ticketType, $payment] = webhookOrder();

    postFakeWebhook($this, webhookPayload($order, 'EVT-PAID'))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_PROCESSED);

    $ticket = Ticket::query()->where('order_id', $order->getKey())->firstOrFail();
    expect($ticket->holder_name)->toBe('Budi Pembeli')
        ->and(NotificationLog::query()->where('template', 'ticket_issued')->count())->toBe(1);
});

it('does not duplicate tickets when a webhook is retried', function () {
    [$order] = webhookOrder();

    $payload = webhookPayload($order, 'EVT-DUPLICATE');

    postFakeWebhook($this, $payload)->assertOk();
    postFakeWebhook($this, $payload)->assertOk();

    expect(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1)
        ->and(WebhookEvent::query()->count())->toBe(1);
});

it('does not downgrade a paid order when a late failure webhook arrives', function () {
    [$order] = webhookOrder();

    postFakeWebhook($this, webhookPayload($order, 'EVT-PAID-FIRST'))->assertOk();
    postFakeWebhook($this, webhookPayload($order, 'EVT-FAILED-LATE', 'failed'))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1);
});

it('marks the payment failed without issuing tickets when a failure webhook arrives before payment', function () {
    [$order, $ticketType, $payment] = webhookOrder();

    $payment->forceFill(['gateway_ref' => 'EVT-FAILED-EARLY'])->save();

    postFakeWebhook($this, webhookPayload($order, 'EVT-FAILED-EARLY', 'failed'))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($ticketType->fresh()->sold_count)->toBe(1)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(0)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_PROCESSED);
});

it('rejects a webhook whose amount does not match the order', function () {
    [$order, , $payment] = webhookOrder();

    postFakeWebhook($this, webhookPayload($order, 'EVT-WRONG-AMOUNT', 'success', 1000))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_FAILED);
});

it('re-enables an expired order when a late payment has stock', function () {
    [$order, $ticketType] = webhookOrder([
        'status' => Order::STATUS_EXPIRED,
    ], ['sold_count' => 0]);

    postFakeWebhook($this, webhookPayload($order, 'EVT-LATE-WITH-STOCK'))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($ticketType->fresh()->sold_count)->toBe(1)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1);
});

it('creates an automatic refund when a late payment has no stock', function () {
    Notification::fake();

    [$order, $ticketType] = webhookOrder([
        'status' => Order::STATUS_EXPIRED,
    ], ['quota' => 1, 'sold_count' => 1]);

    postFakeWebhook($this, webhookPayload($order, 'EVT-LATE-NO-STOCK'))->assertOk();

    $refund = Refund::query()->firstOrFail();

    expect($order->fresh()->status)->toBe(Order::STATUS_EXPIRED)
        ->and($ticketType->fresh()->sold_count)->toBe(1)
        ->and(Ticket::query()->count())->toBe(0)
        ->and($refund->status)->toBe(Refund::STATUS_REQUESTED)
        ->and($refund->amount)->toBe(150000);

    Notification::assertSentOnDemand(OrderRefundRequested::class);
});

it('records an unknown order webhook without changing anything', function () {
    [$order] = webhookOrder();

    $payload = webhookPayload($order, 'EVT-UNKNOWN');
    $payload['order_number'] = 'ORD-UNKNOWN';

    postFakeWebhook($this, $payload)->assertOk();

    expect(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_FAILED)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

it('expires pending orders, returns stock once, and notifies the buyer', function () {
    Notification::fake();

    [$order, $ticketType] = webhookOrder([
        'expires_at' => now()->subMinute(),
    ]);

    (new ExpireOrders)->handle();

    expect($order->fresh()->status)->toBe(Order::STATUS_EXPIRED)
        ->and($ticketType->fresh()->sold_count)->toBe(0);

    Notification::assertSentOnDemand(OrderExpired::class);

    (new ExpireOrders)->handle();

    expect($order->fresh()->status)->toBe(Order::STATUS_EXPIRED)
        ->and($ticketType->fresh()->sold_count)->toBe(0);
});

it('issues tickets idempotently and sends the ticket email once', function () {
    Notification::fake();

    [$order] = webhookOrder(['status' => Order::STATUS_PAID]);

    (new IssueTickets($order->getKey()))->handle();
    (new IssueTickets($order->getKey()))->handle();

    expect(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1)
        ->and(NotificationLog::query()->where('template', 'ticket_issued')->count())->toBe(1);

    Notification::assertSentOnDemand(TicketIssued::class);
});

it('reconciles a pending payment and issues tickets', function () {
    [$order] = webhookOrder();

    $this->mock(FakeGateway::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getStatus')->once()->andReturn(Payment::STATUS_SUCCESS);
    });

    (new ReconcilePayments)->handle(
        app(PaymentReconciliationService::class),
    );

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1);
});
