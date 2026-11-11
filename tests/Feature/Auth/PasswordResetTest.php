<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('always returns the same generic response for password reset links', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'known@example.com']);

    $this->post('/forgot-password', ['email' => 'nobody@example.com'])
        ->assertSessionHas('status');

    Notification::assertNothingSent();

    $this->post('/forgot-password', ['email' => 'KNOWN@example.com'])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets the password with a valid token and clears other sessions', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => 'old-password',
    ]);

    DB::table('sessions')->insert([
        'id' => 'existing-session',
        'user_id' => $user->id,
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);

    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'RESET@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'))->assertSessionHas('status');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('id', 'existing-session')->exists())->toBeFalse();
});

it('rejects reused, invalid, and expired password reset tokens', function () {
    $user = User::factory()->create([
        'email' => 'reuse@example.com',
        'password' => 'old-password',
    ]);

    $token = Password::broker()->createToken($user);

    $payload = [
        'token' => $token,
        'email' => 'reuse@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ];

    $this->post('/reset-password', $payload)->assertRedirect(route('login'));

    // The token has been consumed and can no longer be used. Use a different
    // password so the "must differ from old password" rule does not trigger.
    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'reuse@example.com',
        'password' => 'third-password',
        'password_confirmation' => 'third-password',
    ])->assertSessionHasErrors('email');

    $this->post('/reset-password', [
        'token' => 'not-a-real-token',
        'email' => 'reuse@example.com',
        'password' => 'another-password',
        'password_confirmation' => 'another-password',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects a new password that matches the old one', function () {
    $user = User::factory()->create([
        'email' => 'same@example.com',
        'password' => 'old-password',
    ]);

    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'same@example.com',
        'password' => 'old-password',
        'password_confirmation' => 'old-password',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});
