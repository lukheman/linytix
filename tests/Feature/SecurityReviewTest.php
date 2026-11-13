<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @return array{0: User, 1: Organizer, 2: Event, 3: TicketType}
 */
function securityEventContext(array $eventOverrides = [], array $typeOverrides = []): array
{
    $owner = User::factory()->organizer()->create();

    $organizer = Organizer::factory()->forOwner($owner)->create([
        'name' => 'Security Organizer '.fake()->unique()->word(),
        'slug' => 'security-organizer-'.Str::lower(Str::random(8)),
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    $event = Event::factory()->forOrganizer($organizer)->create(array_merge([
        'title' => 'Security Event '.fake()->unique()->word(),
        'slug' => 'security-event-'.Str::lower(Str::random(8)),
        'status' => Event::STATUS_PUBLISHED,
        'published_at' => now(),
        'start_at' => now()->addDays(7),
        'end_at' => now()->addDays(7)->addHours(3),
    ], $eventOverrides));

    $ticketType = TicketType::factory()->forEvent($event)->create(array_merge([
        'name' => 'Reguler',
        'price' => 150000,
        'quota' => 100,
        'sold_count' => 0,
        'min_per_order' => 1,
        'max_per_order' => 4,
        'is_active' => true,
    ], $typeOverrides));

    return [$owner, $organizer, $event, $ticketType];
}

/**
 * @return array<string, mixed>
 */
function securityCheckoutPayload(TicketType $ticketType, array $overrides = []): array
{
    return array_merge([
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'buyer_name' => 'Budi Security',
        'buyer_email' => 'budi.security@example.com',
        'buyer_phone' => '081234567890',
        'expected_unit_price' => (int) $ticketType->price,
        'idempotency_key' => (string) Str::uuid(),
    ], $overrides);
}

function securityPostWebhook(TestCase $test, array $payload, ?string $signature = null)
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

it('blocks vertical privilege escalation into admin and organizer areas', function () {
    $customer = User::factory()->customer()->create();
    $organizer = User::factory()->organizer()->create();
    Organizer::factory()->forOwner($organizer)->create();
    $staff = User::factory()->staff()->create();
    $admin = User::factory()->admin()->create();

    foreach ([$customer, $organizer, $staff] as $user) {
        $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.orders'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.settings'))->assertForbidden();
    }

    $this->actingAs($customer)->get(route('organizer.events.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('organizer.events.index'))->assertForbidden();
    $this->actingAs($organizer)->get(route('organizer.events.index'))->assertOk();

    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users'))->assertOk();
    $this->actingAs($admin)->get(route('admin.settings'))->assertOk();

    auth()->logout();
    $this->get(route('admin.users'))->assertRedirect(route('login'));
});

it('prevents horizontal access to another organizer event data', function () {
    [$ownerA, , $eventA, $ticketTypeA] = securityEventContext();
    [$ownerB, , $eventB] = securityEventContext();

    foreach ([
        route('organizer.events.edit', $eventA),
        route('organizer.events.report', $eventA),
        route('organizer.events.staff.index', $eventA),
        route('organizer.events.tickets.index', $eventA),
    ] as $url) {
        $this->actingAs($ownerB)->get($url)->assertForbidden();
    }

    $this->actingAs($ownerB)
        ->put(route('organizer.events.update', $eventA), ['description' => 'Diambil alih'])
        ->assertForbidden();

    $this->actingAs($ownerB)
        ->delete(route('organizer.events.destroy', $eventA))
        ->assertForbidden();

    $this->actingAs($ownerB)
        ->post(route('organizer.events.tickets.store', $eventA), [
            'name' => 'Tiket Palsu',
            'price' => 0,
            'quota' => 10,
            'min_per_order' => 1,
            'max_per_order' => 2,
            'is_active' => '1',
        ])
        ->assertForbidden();

    $this->actingAs($ownerA)
        ->get(route('organizer.events.tickets.index', $eventA))
        ->assertOk();

    expect($eventA->fresh()->description)->not->toBe('Diambil alih')
        ->and($eventA->ticketTypes()->whereKey($ticketTypeA->getKey())->exists())->toBeTrue()
        ->and($eventB->fresh()->title)->not->toBe($eventA->title);
});

it('prevents customers from reading another customer order and ticket', function () {
    [, , $event, $ticketType] = securityEventContext();

    $owner = User::factory()->customer()->create();
    $other = User::factory()->customer()->create();

    $order = Order::factory()->paid()->create([
        'user_id' => $owner->getKey(),
        'event_id' => $event->getKey(),
        'buyer_email' => $owner->email,
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);

    $ticket = Ticket::factory()->create([
        'order_id' => $order->getKey(),
        'order_item_id' => $item->getKey(),
        'event_id' => $event->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'holder_name' => 'Pemilik Tiket',
    ]);

    $this->actingAs($other)->get(route('akun.orders.show', $order))->assertForbidden();
    $this->actingAs($other)->get(route('akun.orders.pdf', $order))->assertForbidden();
    $this->actingAs($other)->get(route('akun.tickets.show', $ticket))->assertForbidden();
    $this->actingAs($other)->get(route('akun.tickets.pdf', $ticket))->assertForbidden();

    $this->actingAs($owner)->get(route('akun.orders.show', $order))->assertOk();
    $this->actingAs($owner)->get(route('akun.tickets.show', $ticket))->assertOk();
});

it('blocks staff from scanning events they are not assigned to', function () {
    [, , $eventA] = securityEventContext();
    [, , $eventB, $ticketTypeB] = securityEventContext();

    $order = Order::factory()->paid()->create(['event_id' => $eventB->getKey()]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'ticket_type_id' => $ticketTypeB->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);
    $ticket = Ticket::factory()->create([
        'order_id' => $order->getKey(),
        'order_item_id' => $item->getKey(),
        'event_id' => $eventB->getKey(),
        'ticket_type_id' => $ticketTypeB->getKey(),
    ]);

    $staff = User::factory()->staff()->create();
    $eventA->staff()->attach($staff->getKey(), ['gate' => 'Gate A']);

    $this->actingAs($staff)->get(route('scan.show', $eventB))->assertForbidden();
    $this->actingAs($staff)->postJson(route('scan.store', $eventB), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_ACTIVE);
});

it('keeps csrf protection enabled except for payment webhooks', function () {
    $middleware = app(ValidateCsrfToken::class);

    expect($middleware->getExcludedPaths())->toBe(['webhooks/*']);

    $session = app('session')->driver();
    $session->start();

    $originalEnv = app()['env'];
    app()->instance('env', 'production');

    try {
        $post = Request::create('/checkout/example', 'POST');
        $post->setLaravelSession($session);

        expect(fn () => $middleware->handle($post, fn () => response('ok')))
            ->toThrow(TokenMismatchException::class);

        $webhook = Request::create('/webhooks/payment/fake', 'POST');
        $webhook->setLaravelSession($session);

        expect($middleware->handle($webhook, fn () => response('ok'))->getStatusCode())->toBe(200);
    } finally {
        app()->instance('env', $originalEnv);
    }
});

it('escapes stored xss in event titles and json-ld', function () {
    $payload = '</script><script>alert(1)</script>';

    [, , $event] = securityEventContext([
        'title' => $payload,
        'short_desc' => $payload,
        'description' => 'Deskripsi '.$payload,
    ]);

    $response = $this->get(route('events.show', $event->slug));

    $response->assertOk()
        ->assertDontSee($payload, false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

    expect($response->getContent())
        ->toContain('\u003C/script\u003E')
        ->not->toContain('</script><script>alert(1)</script>');
});

it('does not allow sql injection through public or admin search', function () {
    [, , $firstEvent] = securityEventContext(['title' => 'Event Aman Pertama']);
    [, , $secondEvent] = securityEventContext(['title' => 'Event Aman Kedua']);

    $this->get(route('events.index', ['q' => "' OR 1=1 --"]))
        ->assertOk()
        ->assertDontSee($firstEvent->title)
        ->assertDontSee($secondEvent->title);

    $admin = User::factory()->admin()->create();
    $firstOrder = Order::factory()->create(['event_id' => $firstEvent->getKey()]);
    $secondOrder = Order::factory()->create(['event_id' => $secondEvent->getKey()]);

    $this->actingAs($admin)
        ->get(route('admin.orders', ['q' => "' OR 1=1 --"]))
        ->assertOk()
        ->assertDontSee($firstOrder->order_number)
        ->assertDontSee($secondOrder->order_number);
});

it('ignores mass assignment fields on registration checkout and user update', function () {
    $this->post('/register', [
        'name' => 'Mass Assignment',
        'email' => 'mass@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
        'role' => 'admin',
        'is_active' => '0',
        'email_verified_at' => now()->toIso8601String(),
    ])->assertRedirect(route('akun.dashboard'));

    $registered = User::query()->where('email', 'mass@example.com')->firstOrFail();

    expect($registered->isAdmin())->toBeFalse()
        ->and($registered->isCustomer())->toBeTrue()
        ->and($registered->is_active)->toBeTrue()
        ->and($registered->hasVerifiedEmail())->toBeFalse();

    [, , $event, $ticketType] = securityEventContext();
    $otherUser = User::factory()->customer()->create();

    $this->post(route('checkout.store', $event->slug), securityCheckoutPayload($ticketType, [
        'total_amount' => 1,
        'subtotal' => 1,
        'status' => Order::STATUS_PAID,
        'user_id' => $otherUser->getKey(),
        'paid_at' => now()->toIso8601String(),
    ]))->assertRedirect();

    $order = Order::query()->latest('created_at')->firstOrFail();

    expect($order->total_amount)->toBe(150000)
        ->and($order->status)->toBe(Order::STATUS_PENDING)
        ->and($order->user_id)->toBe($registered->getKey())
        ->and($order->user_id)->not->toBe($otherUser->getKey());

    $admin = User::factory()->admin()->create();
    $target = User::factory()->customer()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => 'Nama Diubah',
            'email' => $target->email,
            'is_active' => '0',
            'roles' => ['admin'],
        ])
        ->assertRedirect(route('admin.users'));

    expect($target->fresh()->is_active)->toBeTrue()
        ->and($target->fresh()->isAdmin())->toBeFalse()
        ->and($target->fresh()->name)->toBe('Nama Diubah');
});

it('rejects dangerous uploads for event posters and avatars', function () {
    Storage::fake('public');

    [$owner] = securityEventContext();
    $category = Category::factory()->create();
    $venue = Venue::factory()->create();

    $this->actingAs($owner)
        ->post(route('organizer.events.store'), [
            'title' => 'Event Upload Berbahaya',
            'category_id' => $category->getKey(),
            'is_online' => '0',
            'venue_id' => $venue->getKey(),
            'timezone' => 'Asia/Jakarta',
            'start_at' => '2026-12-01T19:00',
            'end_at' => '2026-12-01T22:00',
            'description' => 'Deskripsi event.',
            'poster' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])
        ->assertSessionHasErrors('poster');

    expect(Event::query()->where('title', 'Event Upload Berbahaya')->exists())->toBeFalse();

    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->post(route('admin.profile.avatar'), [
            'avatar' => UploadedFile::fake()->create('avatar.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar)->toBeNull();
});

it('verifies webhook signatures and processes replays idempotently', function () {
    [, , $event, $ticketType] = securityEventContext();

    $order = Order::factory()->create([
        'event_id' => $event->getKey(),
        'status' => Order::STATUS_PENDING,
        'expires_at' => now()->addMinutes(15),
        'total_amount' => 150000,
        'subtotal' => 150000,
    ]);

    $order->items()->create([
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);

    $payload = [
        'external_id' => 'SEC-EVT-1',
        'event_type' => 'payment',
        'status' => 'success',
        'order_number' => $order->order_number,
        'amount' => 150000,
    ];

    securityPostWebhook($this, array_merge($payload, ['external_id' => 'SEC-EVT-INVALID']), 'invalid')
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and(WebhookEvent::query()->where('external_id', 'SEC-EVT-INVALID')->firstOrFail()->signature_valid)->toBeFalse();

    securityPostWebhook($this, $payload)->assertOk();
    securityPostWebhook($this, $payload)->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and(Ticket::query()->where('order_id', $order->getKey())->count())->toBe(1)
        ->and(WebhookEvent::query()->count())->toBe(2);
});

it('rate limits repeated webhook traffic', function () {
    config(['ticketing.rate_limits.payment_webhook' => 2]);

    [, , $event] = securityEventContext();

    $order = Order::factory()->create([
        'event_id' => $event->getKey(),
        'status' => Order::STATUS_PENDING,
        'expires_at' => now()->addMinutes(15),
    ]);

    $payload = [
        'event_type' => 'payment',
        'status' => 'success',
        'order_number' => $order->order_number,
        'amount' => 150000,
    ];

    securityPostWebhook($this, array_merge($payload, ['external_id' => 'SEC-LIMIT-1']), 'invalid')
        ->assertForbidden();
    securityPostWebhook($this, array_merge($payload, ['external_id' => 'SEC-LIMIT-2']), 'invalid')
        ->assertForbidden();
    securityPostWebhook($this, array_merge($payload, ['external_id' => 'SEC-LIMIT-3']), 'invalid')
        ->assertStatus(429);
});

it('rejects tampered and expired signed urls', function () {
    [, , $event] = securityEventContext();

    $order = Order::factory()->paid()->create([
        'event_id' => $event->getKey(),
        'buyer_email' => 'signed@example.com',
    ]);

    $valid = URL::temporarySignedRoute('guest.orders.show', now()->addHour(), ['order' => $order]);
    $expired = URL::temporarySignedRoute('guest.orders.show', now()->subMinute(), ['order' => $order]);
    $tampered = $valid.'&tampered=1';

    $this->get($valid)->assertOk();
    $this->get($tampered)->assertForbidden();
    $this->get($expired)->assertForbidden();

    $status = URL::temporarySignedRoute('checkout.status', now()->addHour(), ['order' => $order]);

    $this->getJson($status.'&tampered=1')->assertForbidden();
});

it('keeps environment secrets and logs out of version control', function () {
    $gitignore = File::get(base_path('.gitignore'));

    expect($gitignore)->toContain('.env')
        ->and($gitignore)->toContain('.env.production');

    $logIgnore = base_path('storage/logs/.gitignore');

    expect(File::exists($logIgnore))->toBeTrue()
        ->and(File::get($logIgnore))->toContain('*');

    $trackedEnv = trim((string) shell_exec('git -C '.escapeshellarg(base_path()).' ls-files .env'));

    expect($trackedEnv)->toBe('');
});
