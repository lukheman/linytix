<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Services\Payment\FakeGateway;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('assigns the customer role on registration', function () {
    $this->post('/register', [
        'name' => 'Pembeli Baru',
        'email' => 'pembeli@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
    ])->assertRedirect('/admin/dashboard');

    $user = User::where('email', 'pembeli@example.com')->firstOrFail();

    expect($user->isCustomer())->toBeTrue();
});

it('blocks inactive users from logging in', function () {
    $user = User::factory()->inactive()->create(['password' => 'secret123']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('enforces role middleware', function () {
    Route::middleware(['web', 'auth', 'role:admin'])->get('/_test/admin-only', fn () => 'admin-ok');

    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get('/_test/admin-only')->assertForbidden();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/_test/admin-only')->assertOk()->assertSee('admin-ok');
});

it('lets only the owning organizer update an event', function () {
    $owner = User::factory()->organizer()->create();
    $other = User::factory()->organizer()->create();

    $organizer = Organizer::factory()->forOwner($owner)->create();
    $event = Event::factory()->forOrganizer($organizer)->create();

    expect($owner->can('update', $event))->toBeTrue();
    expect($other->can('update', $event))->toBeFalse();

    $admin = User::factory()->admin()->create();
    expect($admin->can('update', $event))->toBeTrue();
});

it('creates and verifies fake payment charges without network access', function () {
    $gateway = new FakeGateway;

    $order = Order::factory()->create(['total_amount' => 250000]);

    $charge = $gateway->createCharge($order, 'qris');

    expect($charge['gateway_ref'])->toStartWith('FAKE-')
        ->and($charge['payment_url'])->toContain('fake.payment.test');

    $payload = ['external_id' => 'EVT-1', 'status' => 'success', 'order_number' => $order->order_number, 'amount' => 250000];
    $request = Request::create('/webhook', 'POST', $payload, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
    $request->headers->set('X-Fake-Signature', $gateway->signature($request));

    expect($gateway->verifyWebhookSignature($request))->toBeTrue();

    $request->headers->set('X-Fake-Signature', 'invalid');
    expect($gateway->verifyWebhookSignature($request))->toBeFalse();
});

it('registers role based helpers for seeded roles', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::query()->count())->toBeGreaterThanOrEqual(4);

    $user = User::factory()->create();
    $user->assignRole(Role::ORGANIZER);

    expect($user->fresh()->isOrganizer())->toBeTrue();
});
