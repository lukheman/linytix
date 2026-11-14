<?php

use App\Jobs\ReconcilePayments;
use App\Jobs\ReconcileRefunds;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\PaymentReconciliationService;
use App\Services\Refund\RefundService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

function xenditConfig(array $overrides = []): void
{
    config(array_merge([
        'payment.default' => 'xendit',
        'payment.xendit.secret_key' => 'xnd_development_test',
        'payment.xendit.callback_token' => 'test-callback-token',
        'payment.xendit.is_production' => false,
        'payment.xendit.api_base_url' => 'https://api.xendit.co',
        'payment.xendit.session_duration_minutes' => 15,
        'payment.xendit.refund_api_version' => '2024-11-11',
        'payment.xendit.methods' => [
            'qris' => ['QRIS'],
            'bank_transfer' => [
                'BCA_VIRTUAL_ACCOUNT',
                'BNI_VIRTUAL_ACCOUNT',
                'BRI_VIRTUAL_ACCOUNT',
                'MANDIRI_VIRTUAL_ACCOUNT',
                'PERMATA_VIRTUAL_ACCOUNT',
            ],
            'gopay' => ['GOPAY'],
            'shopeepay' => ['SHOPEEPAY'],
        ],
    ], $overrides));
}

/**
 * @return array{0: Order, 1: TicketType}
 */
function xenditOrder(array $orderOverrides = [], array $typeOverrides = []): array
{
    $event = Event::factory()->create([
        'title' => 'Event Xendit',
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
        'buyer_phone' => '081234567890',
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

    return [$order, $ticketType];
}

function xenditPayment(Order $order, array $overrides = []): Payment
{
    return $order->payments()->create(array_merge([
        'gateway' => 'xendit',
        'method' => 'qris',
        'gateway_ref' => 'ps-123',
        'gateway_payment_ref' => 'py-123',
        'amount' => (int) $order->total_amount,
        'status' => Payment::STATUS_PENDING,
        'payment_url' => 'https://checkout.xendit.co/sessions/ps-123',
        'expires_at' => now()->addMinutes(15),
    ], $overrides));
}

/**
 * @return array<string, mixed>
 */
function xenditSessionPayload(Order $order, string $sessionId, string $event = 'payment_session.completed', array $overrides = []): array
{
    return [
        'event' => $event,
        'business_id' => 'biz-123',
        'created' => now()->toIso8601String(),
        'data' => array_merge([
            'payment_session_id' => $sessionId,
            'reference_id' => $order->order_number,
            'session_type' => 'PAY',
            'mode' => 'PAYMENT_LINK',
            'status' => $event === 'payment_session.expired' ? 'EXPIRED' : 'COMPLETED',
            'amount' => (int) $order->total_amount,
            'currency' => 'IDR',
            'payment_id' => 'py-'.$sessionId,
        ], $overrides),
    ];
}

function postXenditSessionWebhook(TestCase $test, array $payload, ?string $token = 'test-callback-token')
{
    return $test->call(
        'POST',
        route('webhooks.payment', 'xendit'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => $token ?? '',
        ],
        json_encode($payload),
    );
}

function postXenditRefundWebhook(TestCase $test, array $payload, ?string $token = 'test-callback-token')
{
    return $test->call(
        'POST',
        route('webhooks.payment.xendit.refund'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => $token ?? '',
        ],
        json_encode($payload),
    );
}

it('creates a xendit payment session and redirects to the hosted payment page', function () {
    xenditConfig();

    Http::fake([
        'api.xendit.co/sessions' => Http::response([
            'payment_session_id' => 'ps-123',
            'reference_id' => 'ignored',
            'status' => 'ACTIVE',
            'payment_link_url' => 'https://checkout.xendit.co/sessions/ps-123',
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ], 201),
    ]);

    [$order] = xenditOrder();

    $payUrl = URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['order' => $order->getKey()]);

    $this->post($payUrl, ['method' => 'qris'])
        ->assertRedirect('https://checkout.xendit.co/sessions/ps-123');

    $payment = $order->payments()->firstOrFail();

    expect($payment->gateway)->toBe('xendit')
        ->and($payment->gateway_ref)->toBe('ps-123')
        ->and($payment->payment_url)->toBe('https://checkout.xendit.co/sessions/ps-123')
        ->and($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);

    Http::assertSent(function ($request) use ($order): bool {
        return $request->url() === 'https://api.xendit.co/sessions'
            && $request['reference_id'] === $order->order_number
            && $request['session_type'] === 'PAY'
            && $request['mode'] === 'PAYMENT_LINK'
            && $request['amount'] === 150000
            && $request['currency'] === 'IDR'
            && $request['country'] === 'ID'
            && $request['allowed_payment_channels'] === ['QRIS']
            && $request['customer']['reference_id'] === 'cust'.preg_replace('/[^A-Za-z0-9]/', '', $order->order_number)
            && $request['customer']['individual_detail']['given_names'] === 'Budi Pembeli'
            && $request['customer']['mobile_number'] === '+6281234567890'
            && str_starts_with($request['success_return_url'], 'https://')
            && str_starts_with($request['cancel_return_url'], 'https://')
            && filled($request['expires_at']);
    });
});

it('uses all virtual account channels for the bank transfer method', function () {
    xenditConfig();

    Http::fake([
        'api.xendit.co/sessions' => Http::response([
            'payment_session_id' => 'ps-va',
            'payment_link_url' => 'https://checkout.xendit.co/sessions/ps-va',
            'status' => 'ACTIVE',
        ], 201),
    ]);

    [$order] = xenditOrder();

    $payUrl = URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['order' => $order->getKey()]);

    $this->post($payUrl, ['method' => 'bank_transfer'])
        ->assertRedirect('https://checkout.xendit.co/sessions/ps-va');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.xendit.co/sessions'
            && $request['allowed_payment_channels'] === [
                'BCA_VIRTUAL_ACCOUNT',
                'BNI_VIRTUAL_ACCOUNT',
                'BRI_VIRTUAL_ACCOUNT',
                'MANDIRI_VIRTUAL_ACCOUNT',
                'PERMATA_VIRTUAL_ACCOUNT',
            ];
    });
});

it('keeps the order pending when xendit rejects the session', function () {
    xenditConfig();

    Http::fake([
        'api.xendit.co/sessions' => Http::response(['error_code' => 'API_VALIDATION_ERROR', 'message' => 'Invalid request'], 400),
    ]);

    [$order] = xenditOrder();

    $payUrl = URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['order' => $order->getKey()]);

    $this->post($payUrl, ['method' => 'qris'])->assertSessionHasErrors('payment');

    expect($order->payments()->count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

it('only offers payment methods supported by the active gateway', function () {
    xenditConfig(['payment.xendit.methods' => ['qris' => ['QRIS']]]);

    [$order] = xenditOrder();

    $signedUrl = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order->getKey()]);

    $this->get($signedUrl)
        ->assertOk()
        ->assertSee('QRIS')
        ->assertDontSee('Transfer Bank')
        ->assertDontSee('ShopeePay');
});

it('marks a pending order paid from a valid xendit session callback', function () {
    xenditConfig();

    [$order] = xenditOrder();
    $payment = xenditPayment($order);

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123'))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS)
        ->and($payment->fresh()->gateway_payment_ref)->toBe('py-ps-123')
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_PROCESSED)
        ->and(WebhookEvent::query()->firstOrFail()->event_type)->toBe('payment_session.completed');
});

it('rejects a xendit callback with an invalid token', function () {
    xenditConfig();

    [$order] = xenditOrder();

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123'), 'wrong-token')
        ->assertForbidden();

    $event = WebhookEvent::query()->firstOrFail();

    expect($event->signature_valid)->toBeFalse()
        ->and($event->status)->toBe(WebhookEvent::STATUS_INVALID_SIGNATURE)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

it('fails closed when the xendit callback token is not configured', function () {
    xenditConfig(['payment.xendit.callback_token' => '']);

    [$order] = xenditOrder();

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123'))
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_INVALID_SIGNATURE);
});

it('does not duplicate tickets when xendit retries a completed session callback', function () {
    xenditConfig();

    [$order] = xenditOrder();
    xenditPayment($order);

    $payload = xenditSessionPayload($order, 'ps-123');

    postXenditSessionWebhook($this, $payload)->assertOk();
    postXenditSessionWebhook($this, $payload)->assertOk();

    expect(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1)
        ->and(WebhookEvent::query()->count())->toBe(1);
});

it('does not attach a new xendit session callback to an older pending session', function () {
    xenditConfig();

    [$order] = xenditOrder();
    $oldPayment = xenditPayment($order, [
        'gateway_ref' => 'ps-old',
        'gateway_payment_ref' => null,
    ]);

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-new'))->assertOk();

    $newPayment = $order->payments()->where('gateway_ref', 'ps-new')->firstOrFail();

    expect($oldPayment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($oldPayment->fresh()->gateway_ref)->toBe('ps-old')
        ->and($newPayment->gateway_payment_ref)->toBe('py-ps-new')
        ->and($newPayment->status)->toBe(Payment::STATUS_SUCCESS);
});

it('marks a payment expired when xendit sends an expired session callback', function () {
    xenditConfig();

    [$order] = xenditOrder();
    $payment = xenditPayment($order);

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123', 'payment_session.expired'))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_EXPIRED)
        ->and(Ticket::query()->count())->toBe(0);
});

it('does not downgrade a paid order when an expired session callback arrives late', function () {
    xenditConfig();

    [$order] = xenditOrder();
    $payment = xenditPayment($order);

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123'))->assertOk();
    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123', 'payment_session.expired'))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1);
});

it('rejects a callback with a non IDR currency', function () {
    xenditConfig();

    [$order] = xenditOrder();
    $payment = xenditPayment($order);

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123', 'payment_session.completed', [
        'currency' => 'USD',
    ]))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_FAILED);
});

it('rejects a callback whose amount does not match the order', function () {
    xenditConfig();

    [$order] = xenditOrder();
    $payment = xenditPayment($order);

    postXenditSessionWebhook($this, xenditSessionPayload($order, 'ps-123', 'payment_session.completed', [
        'amount' => 1000,
    ]))->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_FAILED);
});

it('sends a full refund to xendit v3 and marks it succeeded', function () {
    xenditConfig();

    Http::fake([
        'api.xendit.co/v3/refunds' => Http::response([
            'refund_id' => 'rfd-123',
            'status' => 'SUCCEEDED',
        ], 200),
    ]);

    [$order] = xenditOrder(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    $payment = xenditPayment($order, ['status' => Payment::STATUS_SUCCESS, 'paid_at' => now()]);

    $admin = User::factory()->admin()->create();

    $refund = app(RefundService::class)->requestFullRefund($order, $admin, 'Event dibatalkan');

    expect($refund->fresh()->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and($refund->fresh()->gateway_ref)->toBe('rfd-123')
        ->and($order->fresh()->status)->toBe(Order::STATUS_REFUNDED)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_REFUNDED);

    Http::assertSent(function ($request) use ($payment): bool {
        return $request->url() === 'https://api.xendit.co/v3/refunds'
            && $request->hasHeader('api-version', '2024-11-11')
            && $request['payment_id'] === $payment->gateway_payment_ref
            && $request['amount'] === 150000
            && $request['reason'] === 'REQUESTED_BY_CUSTOMER';
    });
});

it('fails a virtual account refund with a manual refund message', function () {
    xenditConfig();

    Http::fake();

    [$order] = xenditOrder(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    xenditPayment($order, [
        'method' => 'bank_transfer',
        'status' => Payment::STATUS_SUCCESS,
        'paid_at' => now(),
    ]);

    $admin = User::factory()->admin()->create();

    $refund = app(RefundService::class)->requestFullRefund($order, $admin, 'Event dibatalkan');

    expect($refund->fresh()->status)->toBe(Refund::STATUS_FAILED)
        ->and($refund->fresh()->error)->toContain('refund manual')
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID);

    Http::assertNothingSent();
});

it('keeps a pending xendit refund processing until the refund callback arrives', function () {
    xenditConfig();

    Http::fake([
        'api.xendit.co/v3/refunds' => Http::response([
            'refund_id' => 'rfd-pending',
            'status' => 'PENDING',
        ], 200),
    ]);

    [$order] = xenditOrder(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    xenditPayment($order, ['status' => Payment::STATUS_SUCCESS, 'paid_at' => now()]);

    $admin = User::factory()->admin()->create();

    $refund = app(RefundService::class)->requestFullRefund($order, $admin, 'Event dibatalkan');

    expect($refund->fresh()->status)->toBe(Refund::STATUS_PROCESSING)
        ->and($refund->fresh()->gateway_ref)->toBe('rfd-pending')
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID);

    postXenditRefundWebhook($this, [
        'event' => 'refund.succeeded',
        'api_version' => 'v3',
        'data' => [
            'refund_id' => 'rfd-pending',
            'reference_id' => 'refund-'.$order->order_number,
            'payment_id' => 'py-123',
            'amount' => 150000,
            'status' => 'SUCCEEDED',
            'currency' => 'IDR',
        ],
    ])->assertOk();

    expect($refund->fresh()->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and($order->fresh()->status)->toBe(Order::STATUS_REFUNDED);
});

it('rejects a refund callback whose amount does not match the refund', function () {
    xenditConfig();

    [$order] = xenditOrder(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    $payment = xenditPayment($order, ['status' => Payment::STATUS_SUCCESS, 'paid_at' => now()]);

    $refund = Refund::query()->create([
        'order_id' => $order->getKey(),
        'payment_id' => $payment->getKey(),
        'amount' => 150000,
        'reason' => 'Event dibatalkan',
        'status' => Refund::STATUS_PROCESSING,
        'gateway_ref' => 'rfd-mismatch',
    ]);

    postXenditRefundWebhook($this, [
        'event' => 'refund.succeeded',
        'api_version' => 'v3',
        'data' => [
            'refund_id' => 'rfd-mismatch',
            'reference_id' => 'refund-'.$order->order_number,
            'payment_id' => 'py-123',
            'amount' => 1000,
            'status' => 'SUCCEEDED',
            'currency' => 'IDR',
        ],
    ])->assertOk();

    expect($refund->fresh()->status)->toBe(Refund::STATUS_PROCESSING)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and(WebhookEvent::query()->firstOrFail()->status)->toBe(WebhookEvent::STATUS_FAILED);
});

it('reconciles a pending xendit payment using the session status', function () {
    xenditConfig();

    Http::fake([
        'api.xendit.co/sessions/ps-123' => Http::response([
            'payment_session_id' => 'ps-123',
            'status' => 'COMPLETED',
        ], 200),
    ]);

    [$order] = xenditOrder();
    $payment = xenditPayment($order);

    (new ReconcilePayments)->handle(app(PaymentReconciliationService::class));

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1);
});

it('reconciles a processing xendit refund using the refund status', function () {
    xenditConfig();

    Http::fake([
        'api.xendit.co/v3/refunds/rfd-processing' => Http::response([
            'refund_id' => 'rfd-processing',
            'status' => 'SUCCEEDED',
        ], 200),
    ]);

    [$order] = xenditOrder(['status' => Order::STATUS_PAID, 'paid_at' => now()]);
    $payment = xenditPayment($order, ['status' => Payment::STATUS_SUCCESS, 'paid_at' => now()]);

    $refund = Refund::query()->create([
        'order_id' => $order->getKey(),
        'payment_id' => $payment->getKey(),
        'amount' => 150000,
        'reason' => 'Event dibatalkan',
        'status' => Refund::STATUS_PROCESSING,
        'gateway_ref' => 'rfd-processing',
    ]);

    (new ReconcileRefunds)->handle(app(PaymentManager::class), app(RefundService::class));

    expect($refund->fresh()->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and($order->fresh()->status)->toBe(Order::STATUS_REFUNDED);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.xendit.co/v3/refunds/rfd-processing'
            && $request->hasHeader('api-version', '2024-11-11');
    });
});
