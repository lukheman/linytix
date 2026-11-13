<?php

use App\Models\Order;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('registers a new customer, normalizes the email, and queues verification', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Budi Santoso',
        'email' => '  Budi@Example.COM ',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
    ])->assertRedirect('/akun');

    $user = User::where('email', 'budi@example.com')->firstOrFail();

    expect($user->isCustomer())->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('carries organizer intent from the signup call to action', function () {
    Notification::fake();

    $this->get(route('register', ['intent' => 'organizer']))
        ->assertOk()
        ->assertSee('Anda mendaftar untuk menjadi organizer.')
        ->assertSee('name="intent" value="organizer"', false);

    $this->post('/register', [
        'name' => 'Calon Organizer',
        'email' => 'calon@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
        'intent' => 'organizer',
    ])->assertRedirect('/akun')
        ->assertSessionHas('status', fn ($status) => str_contains($status, 'lanjutkan pendaftaran organizer'));

    expect(User::where('email', 'calon@example.com')->firstOrFail()->isCustomer())->toBeTrue();
});

it('rejects weak passwords, mismatched confirmation, and duplicate emails', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/register', [
        'name' => 'Too Short',
        'email' => 'short@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
        'agree_terms' => '1',
    ])->assertSessionHasErrors('password');

    $this->post('/register', [
        'name' => 'Mismatch',
        'email' => 'mismatch@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different123',
        'agree_terms' => '1',
    ])->assertSessionHasErrors('password');

    $this->post('/register', [
        'name' => 'Duplicate',
        'email' => 'TAKEN@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
    ])->assertSessionHasErrors('email');

    expect(User::where('email', 'short@example.com')->exists())->toBeFalse()
        ->and(User::where('email', 'mismatch@example.com')->exists())->toBeFalse()
        ->and(User::count())->toBe(1);
});

it('logs in with a case-insensitive email and allows unverified users', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'login@example.com',
        'password' => 'secret123',
    ]);

    $this->post('/login', [
        'email' => 'LOGIN@example.com',
        'password' => 'secret123',
    ])->assertRedirect('/akun');

    $this->assertAuthenticatedAs($user);
});

it('shows the same generic error for wrong passwords and unknown emails', function () {
    $user = User::factory()->create([
        'email' => 'known@example.com',
        'password' => 'secret123',
    ]);

    $this->from('/login')->post('/login', [
        'email' => 'known@example.com',
        'password' => 'wrong-password',
    ])->assertRedirect('/login')->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->from('/login')->post('/login', [
        'email' => 'nobody@example.com',
        'password' => 'whatever123',
    ])->assertRedirect('/login')->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
    expect($user->fresh()->last_login_at)->toBeNull();
});

it('throttles repeated login attempts for the same email and ip', function () {
    $user = User::factory()->create([
        'email' => 'throttled@example.com',
        'password' => 'secret123',
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => 'throttled@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    // Even the correct password is rejected once the limiter is exhausted.
    $this->post('/login', [
        'email' => 'throttled@example.com',
        'password' => 'secret123',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

it('keeps the admin logout redirecting to the login page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertSee('action="'.route('admin.logout').'"', false);

    $this->actingAs($user)
        ->post(route('admin.logout'))
        ->assertRedirect('/login');

    $this->assertGuest();
});

it('verifies an email through a signed link and claims matching guest orders', function () {
    $user = User::factory()->unverified()->create(['email' => 'guest-owner@example.com']);
    $order = Order::factory()->create([
        'user_id' => null,
        'buyer_email' => 'guest-owner@example.com',
    ]);

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect('/akun');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($order->fresh()->user_id)->toBe($user->id);
});

it('rejects tampered email verification links', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)->get($url.'&tampered=1')->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('shows the verification notice with the public auth layout', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Verifikasi email Anda.')
        ->assertSee('Kirim ulang email verifikasi')
        ->assertSee('Seluruh hak cipta');
});

it('sends an organizer to the organizer dashboard instead of an admin intended url', function () {
    $organizer = User::factory()->organizer()->create([
        'email' => 'organizer-login@example.com',
        'password' => 'secret123',
    ]);

    $this->get('/admin/pesanan')->assertRedirect('/login');

    $this->post('/login', [
        'email' => 'organizer-login@example.com',
        'password' => 'secret123',
    ])->assertRedirect(route('organizer.dashboard'))
        ->assertSessionHas('warning');

    $this->assertAuthenticatedAs($organizer);
});

it('honors an admin intended url for an admin', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin-login@example.com',
        'password' => 'secret123',
    ]);

    $this->get('/admin/pesanan')->assertRedirect('/login');

    $this->post('/login', [
        'email' => 'admin-login@example.com',
        'password' => 'secret123',
    ])->assertRedirect('/admin/pesanan');

    $this->assertAuthenticatedAs($admin);
});
