<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @return array{0: Event, 1: TicketType}
 */
function uatEventContext(array $eventOverrides = [], array $typeOverrides = []): array
{
    $owner = User::factory()->organizer()->create();
    $organizer = Organizer::factory()->forOwner($owner)->create([
        'name' => 'UAT Organizer',
        'slug' => 'uat-organizer-'.Str::lower(Str::random(6)),
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    $event = Event::factory()->forOrganizer($organizer)->create(array_merge([
        'title' => 'Konser UAT '.fake()->unique()->word(),
        'slug' => 'konser-uat-'.Str::lower(Str::random(8)),
        'status' => Event::STATUS_PUBLISHED,
        'published_at' => now(),
        'start_at' => now()->addDays(14),
        'end_at' => now()->addDays(14)->addHours(3),
    ], $eventOverrides));

    $ticketType = TicketType::factory()->forEvent($event)->create(array_merge([
        'name' => 'Reguler UAT',
        'price' => 150000,
        'quota' => 50,
        'sold_count' => 0,
        'min_per_order' => 1,
        'max_per_order' => 4,
        'is_active' => true,
    ], $typeOverrides));

    return [$event, $ticketType];
}

/**
 * @return array<string, mixed>
 */
function uatCheckoutPayload(TicketType $ticketType, array $overrides = []): array
{
    return array_merge([
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'buyer_name' => 'Budi UAT',
        'buyer_email' => 'budi.uat@example.com',
        'buyer_phone' => '081234567890',
        'expected_unit_price' => (int) $ticketType->price,
        'idempotency_key' => (string) Str::uuid(),
    ], $overrides);
}

function uatPostWebhook(TestCase $test, Order $order, string $externalId): void
{
    $payload = [
        'external_id' => $externalId,
        'event_type' => 'payment',
        'status' => 'success',
        'order_number' => $order->order_number,
        'amount' => (int) $order->total_amount,
    ];

    $json = json_encode($payload);
    $signature = hash_hmac('sha256', $json, (string) config('payment.fake.secret', 'fake-secret'));

    $test->call(
        'POST',
        route('webhooks.payment', 'fake'),
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_X_FAKE_SIGNATURE' => $signature],
        $json,
    )->assertOk();
}

it('completes the guest purchase journey from checkout to e-ticket', function () {
    Notification::fake();

    [$event, $ticketType] = uatEventContext();

    $this->post(route('checkout.store', $event->slug), uatCheckoutPayload($ticketType))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->status)->toBe(Order::STATUS_PENDING)
        ->and($order->user_id)->toBeNull()
        ->and($ticketType->fresh()->sold_count)->toBe(1);

    $checkoutUrl = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order]);
    $this->get($checkoutUrl)
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('QRIS');

    $payUrl = URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['order' => $order]);
    $this->post($payUrl, ['method' => 'qris'])->assertRedirect();

    $payment = $order->payments()->firstOrFail();

    expect($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->payment_url)->toContain('fake.payment.test');

    uatPostWebhook($this, $order, (string) $payment->gateway_ref);

    $ticket = Ticket::query()->where('order_id', $order->getKey())->firstOrFail();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS)
        ->and($ticket->status)->toBe(Ticket::STATUS_ACTIVE)
        ->and($ticket->holder_name)->toBe('Budi UAT')
        ->and(NotificationLog::query()->where('order_id', $order->getKey())->where('template', 'ticket_issued')->count())->toBe(1);

    $guestUrl = URL::temporarySignedRoute('guest.orders.show', now()->addHour(), ['order' => $order]);

    $this->get($guestUrl)
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee($event->title)
        ->assertSee($ticket->ticket_code);

    $statusUrl = URL::temporarySignedRoute('checkout.status', now()->addHour(), ['order' => $order]);

    $this->getJson($statusUrl)
        ->assertOk()
        ->assertJson([
            'status' => Order::STATUS_PAID,
            'has_tickets' => true,
        ]);
});

it('shows a logged-in customer order and ticket in the account area', function () {
    Notification::fake();

    [$event, $ticketType] = uatEventContext();
    $customer = User::factory()->customer()->create([
        'email' => 'customer.uat@example.com',
    ]);

    $this->actingAs($customer)
        ->post(route('checkout.store', $event->slug), uatCheckoutPayload($ticketType, [
            'buyer_email' => $customer->email,
        ]))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->user_id)->toBe($customer->getKey());

    uatPostWebhook($this, $order, 'UAT-CUSTOMER-1');

    $ticket = Ticket::query()->where('order_id', $order->getKey())->firstOrFail();

    $this->actingAs($customer)
        ->get(route('akun.orders.index'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee($event->title);

    $this->actingAs($customer)
        ->get(route('akun.orders.show', $order))
        ->assertOk()
        ->assertSee($event->title)
        ->assertSee('E-tiket')
        ->assertSee('Reguler UAT');

    $this->actingAs($customer)
        ->get(route('akun.tickets.index'))
        ->assertOk()
        ->assertSee($event->title)
        ->assertSee(Str::limit($ticket->ticket_code, 16));
});

it('lets an organizer create submit and publish an event end to end', function () {
    Notification::fake();
    Storage::fake('public');

    $owner = User::factory()->organizer()->create();
    $organizer = Organizer::factory()->forOwner($owner)->create([
        'name' => 'UAT Live',
        'slug' => 'uat-live',
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    $category = Category::factory()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($owner)
        ->post(route('organizer.events.store'), [
            'title' => 'Festival UAT 2026',
            'category_id' => $category->getKey(),
            'is_online' => '0',
            'venue_id' => $venue->getKey(),
            'timezone' => 'Asia/Jakarta',
            'start_at' => '2026-12-20T19:00',
            'end_at' => '2026-12-20T22:00',
            'doors_open_at' => '2026-12-20T18:00',
            'short_desc' => 'Festival uji terima.',
            'description' => 'Deskripsi lengkap festival uji terima.',
            'poster' => UploadedFile::fake()->image('poster.jpg', 1200, 800),
        ])
        ->assertRedirect();

    $event = Event::query()->where('title', 'Festival UAT 2026')->firstOrFail();

    expect($event->organizer_id)->toBe($organizer->getKey())
        ->and($event->status)->toBe(Event::STATUS_DRAFT);

    $this->actingAs($owner)
        ->post(route('organizer.events.tickets.store', $event), [
            'name' => 'Tiket Festival',
            'price' => 200000,
            'quota' => 100,
            'min_per_order' => 1,
            'max_per_order' => 4,
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($owner)
        ->post(route('organizer.events.submit', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.approve', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    $event->refresh();

    expect($event->status)->toBe(Event::STATUS_PUBLISHED)
        ->and($event->published_at)->not->toBeNull()
        ->and($event->approved_by)->toBe($admin->getKey());

    $this->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertSee('Festival UAT 2026')
        ->assertSee('Tiket Festival');

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('Festival UAT 2026');
});
