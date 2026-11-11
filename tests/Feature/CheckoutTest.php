<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\Payment\FakeGateway;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mockery\MockInterface;

function checkoutEvent(array $eventAttributes = [], array $typeAttributes = []): array
{
    $event = Event::factory()->create(array_merge([
        'title' => 'Konser Checkout',
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(7),
    ], $eventAttributes));

    $ticketType = TicketType::factory()->forEvent($event)->create(array_merge([
        'name' => 'Reguler',
        'price' => 150000,
        'quota' => 10,
        'sold_count' => 0,
        'min_per_order' => 1,
        'max_per_order' => 4,
        'is_active' => true,
    ], $typeAttributes));

    return [$event, $ticketType];
}

function checkoutPayload(TicketType $ticketType, array $overrides = []): array
{
    return array_merge([
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'buyer_name' => 'Budi Pembeli',
        'buyer_email' => 'Budi@Example.com',
        'buyer_phone' => '081234567890',
        'expected_unit_price' => (int) $ticketType->price,
        'idempotency_key' => (string) Str::uuid(),
    ], $overrides);
}

it('shows the checkout form with ticket types and buyer fields', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->get(route('checkout.create', $event->slug))
        ->assertOk()
        ->assertSee('Konser Checkout')
        ->assertSee('Reguler')
        ->assertSee('name="buyer_email"', false)
        ->assertSee('name="idempotency_key"', false);

    $this->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertSee(route('checkout.create', ['event' => $event->slug, 'ticket_type' => $ticketType->getKey()]), false);
});

it('creates a pending order and reserves stock atomically', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType, [
        'quantity' => 2,
        'buyer_email' => 'BUDI@Example.com',
    ]))->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->status)->toBe(Order::STATUS_PENDING)
        ->and($order->user_id)->toBeNull()
        ->and($order->buyer_email)->toBe('budi@example.com')
        ->and($order->subtotal)->toBe(300000)
        ->and($order->total_amount)->toBe(300000)
        ->and($order->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)))->toBeTrue()
        ->and($order->items()->count())->toBe(1)
        ->and($order->items->first()->quantity)->toBe(2)
        ->and($order->tickets()->count())->toBe(0)
        ->and($ticketType->fresh()->sold_count)->toBe(2);
});

it('allows only one order for the last remaining ticket', function () {
    [$event, $ticketType] = checkoutEvent([], ['quota' => 1]);

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))->assertRedirect();
    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))
        ->assertSessionHasErrors('ticket_type_id');

    expect(Order::query()->count())->toBe(1)
        ->and($ticketType->fresh()->sold_count)->toBe(1);
});

it('rejects quantities outside the ticket limits and quota', function () {
    [$event, $ticketType] = checkoutEvent([], ['quota' => 1, 'max_per_order' => 2]);

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType, ['quantity' => 2]))
        ->assertSessionHasErrors('ticket_type_id');

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType, ['quantity' => 3]))
        ->assertSessionHasErrors('quantity');

    expect(Order::query()->count())->toBe(0)
        ->and($ticketType->fresh()->sold_count)->toBe(0);
});

it('rejects tickets outside the sale window or inactive', function () {
    [$event, $ticketType] = checkoutEvent([], ['sale_start_at' => now()->addDay()]);

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))
        ->assertSessionHasErrors('ticket_type_id');

    $ticketType->forceFill(['sale_start_at' => null, 'sale_end_at' => now()->subDay()])->save();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))
        ->assertSessionHasErrors('ticket_type_id');

    $ticketType->forceFill(['sale_end_at' => null, 'is_active' => false])->save();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))
        ->assertSessionHasErrors('ticket_type_id');

    expect(Order::query()->count())->toBe(0);
});

it('requires holder names when the event requires identity', function () {
    [$event, $ticketType] = checkoutEvent(['require_identity' => true]);

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType, [
        'quantity' => 2,
        'holder_names' => ['Budi'],
    ]))->assertSessionHasErrors('holder_names');

    expect(Order::query()->count())->toBe(0);

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType, [
        'quantity' => 2,
        'holder_names' => ['Budi', 'Sari'],
    ]))->assertRedirect();

    expect(Order::query()->firstOrFail()->holder_names)->toBe(['Budi', 'Sari']);
});

it('rejects checkout when the price changed after the form loaded', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType, [
        'expected_unit_price' => 1000,
    ]))->assertSessionHasErrors('ticket_type_id');

    expect(Order::query()->count())->toBe(0)
        ->and($ticketType->fresh()->sold_count)->toBe(0);
});

it('limits the number of pending orders per identity', function () {
    config(['ticketing.max_pending_orders_per_identity' => 2]);

    [$event, $ticketType] = checkoutEvent();

    foreach (range(1, 2) as $index) {
        Order::factory()->create([
            'event_id' => $event->getKey(),
            'buyer_email' => 'budi@example.com',
            'status' => Order::STATUS_PENDING,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))
        ->assertSessionHasErrors('buyer_email');

    expect(Order::query()->count())->toBe(2);
});

it('prevents duplicate orders with the same idempotency key', function () {
    [$event, $ticketType] = checkoutEvent();

    $payload = checkoutPayload($ticketType);

    $this->post(route('checkout.store', $event->slug), $payload)->assertRedirect();
    $this->post(route('checkout.store', $event->slug), $payload)->assertRedirect();

    expect(Order::query()->count())->toBe(1)
        ->and($ticketType->fresh()->sold_count)->toBe(1);
});

it('initiates a fake payment for a guest order', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))->assertRedirect();

    $order = Order::query()->firstOrFail();
    $payUrl = URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['order' => $order->getKey()]);

    $this->post($payUrl, ['method' => 'qris'])->assertRedirect();

    $payment = $order->payments()->firstOrFail();

    expect($payment->status)->toBe('pending')
        ->and($payment->method)->toBe('qris')
        ->and($payment->amount)->toBe(150000)
        ->and($payment->payment_url)->toContain('fake.payment.test')
        ->and($payment->expires_at->lessThanOrEqualTo($order->expires_at))->toBeTrue()
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

it('keeps the order pending when the payment gateway fails', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))->assertRedirect();

    $order = Order::query()->firstOrFail();
    $payUrl = URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['order' => $order->getKey()]);

    $this->mock(FakeGateway::class, function (MockInterface $mock): void {
        $mock->shouldReceive('createCharge')->once()->andThrow(new RuntimeException('Gateway down'));
    });

    $this->post($payUrl, ['method' => 'qris'])->assertSessionHasErrors('payment');

    expect($order->payments()->count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

it('protects the payment status page with signed urls or ownership', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))->assertRedirect();

    $order = Order::query()->firstOrFail();

    $this->get(route('checkout.show', $order))->assertForbidden();

    $signedUrl = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order->getKey()]);
    $this->get($signedUrl)->assertOk()->assertSee($order->order_number);

    $this->actingAs(User::factory()->create())->get(route('checkout.show', $order))->assertForbidden();

    $owner = User::factory()->create();
    $order->forceFill(['user_id' => $owner->getKey()])->save();

    $this->actingAs($owner)->get(route('checkout.show', $order))->assertOk();
});

it('exposes a json status endpoint for payment polling', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))->assertRedirect();

    $order = Order::query()->firstOrFail();
    $statusUrl = URL::temporarySignedRoute('checkout.status', now()->addHour(), ['order' => $order->getKey()]);

    $this->getJson($statusUrl)
        ->assertOk()
        ->assertJson([
            'status' => Order::STATUS_PENDING,
            'expired' => false,
            'has_tickets' => false,
        ]);

    $order->forceFill(['status' => Order::STATUS_PAID, 'paid_at' => now()])->save();

    $this->getJson($statusUrl)
        ->assertOk()
        ->assertJson([
            'status' => Order::STATUS_PAID,
            'expired' => false,
        ]);
});

it('reports a pending order past its deadline as expired through polling', function () {
    [$event] = checkoutEvent();

    $order = Order::factory()->create([
        'event_id' => $event->getKey(),
        'status' => Order::STATUS_PENDING,
        'expires_at' => now()->subMinute(),
    ]);

    $statusUrl = URL::temporarySignedRoute('checkout.status', now()->addHour(), ['order' => $order->getKey()]);

    $this->getJson($statusUrl)
        ->assertOk()
        ->assertJson([
            'status' => Order::STATUS_PENDING,
            'expired' => true,
        ]);
});

it('protects the polling endpoint with signed urls or ownership', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))->assertRedirect();

    $order = Order::query()->firstOrFail();

    $this->getJson(route('checkout.status', $order))->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->getJson(route('checkout.status', $order))
        ->assertForbidden();

    $owner = User::factory()->create();
    $order->forceFill(['user_id' => $owner->getKey()])->save();

    $this->actingAs($owner)
        ->getJson(route('checkout.status', $order))
        ->assertOk();
});

it('renders automatic polling on the pending payment page', function () {
    [$event, $ticketType] = checkoutEvent();

    $this->post(route('checkout.store', $event->slug), checkoutPayload($ticketType))->assertRedirect();

    $order = Order::query()->firstOrFail();
    $signedUrl = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order->getKey()]);

    $this->get($signedUrl)
        ->assertOk()
        ->assertSee('data-payment-poll-status', false)
        ->assertSee('setTimeout(poll, interval)', false);
});

it('shows an expired state and issues tickets only after payment', function () {
    [$event, $ticketType] = checkoutEvent();

    $order = Order::factory()->expired()->create([
        'event_id' => $event->getKey(),
        'buyer_email' => 'budi@example.com',
    ]);

    $signedUrl = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order->getKey()]);

    $this->get($signedUrl)->assertOk()->assertSee('kedaluwarsa');

    expect(Ticket::query()->count())->toBe(0);
});

it('shows the paid state with a link to tickets', function () {
    [$event] = checkoutEvent();

    $order = Order::factory()->paid()->create([
        'event_id' => $event->getKey(),
        'buyer_email' => 'budi@example.com',
    ]);

    $signedUrl = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order->getKey()]);

    $this->get($signedUrl)
        ->assertOk()
        ->assertSee('Pembayaran berhasil')
        ->assertSee('Lihat tiket');
});
