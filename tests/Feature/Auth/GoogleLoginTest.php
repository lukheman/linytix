<?php

use App\Models\Order;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('redirects to login when Google OAuth is not configured', function () {
    config([
        'services.google.client_id' => null,
        'services.google.client_secret' => null,
    ]);

    $this->get(route('auth.google.redirect'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});

it('creates a verified customer account from a Google callback', function () {
    $order = Order::factory()->create([
        'user_id' => null,
        'buyer_email' => 'new.google@example.com',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn(SocialiteUser::fake([
        'id' => 'google-new-1',
        'name' => 'Google Person',
        'email' => 'New.Google@Example.com',
        'avatar' => 'https://example.test/avatar.jpg',
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect('/akun');

    $user = User::where('email', 'new.google@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);

    expect($user->google_id)->toBe('google-new-1')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->isCustomer())->toBeTrue()
        ->and($order->fresh()->user_id)->toBe($user->id);
});

it('logs in an existing verified user and links the Google id', function () {
    $user = User::factory()->create([
        'email' => 'linked@example.com',
        'google_id' => null,
    ]);

    Socialite::shouldReceive('driver->user')->andReturn(SocialiteUser::fake([
        'id' => 'google-linked-2',
        'email' => 'linked@example.com',
        'avatar' => 'https://example.test/linked.jpg',
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect('/akun');

    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->google_id)->toBe('google-linked-2')
        ->and($user->fresh()->avatar)->toBe('https://example.test/linked.jpg');
});

it('refuses Google login for an existing unverified email', function () {
    $user = User::factory()->unverified()->create(['email' => 'unverified@example.com']);

    Socialite::shouldReceive('driver->user')->andReturn(SocialiteUser::fake([
        'id' => 'google-unverified',
        'email' => 'unverified@example.com',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($user->fresh()->google_id)->toBeNull();
});

it('refuses to link an email that belongs to another Google account', function () {
    User::factory()->create([
        'email' => 'conflict@example.com',
        'google_id' => 'original-google-id',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn(SocialiteUser::fake([
        'id' => 'different-google-id',
        'email' => 'conflict@example.com',
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('fails safely when the Google OAuth state is invalid', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new RuntimeException('Invalid state'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
