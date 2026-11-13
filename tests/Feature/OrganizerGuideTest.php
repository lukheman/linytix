<?php

use App\Models\Organizer;
use App\Models\User;

it('shows the public organizer guide', function () {
    $this->get(route('organizer.guide'))
        ->assertOk()
        ->assertSee('Panduan mendaftarkan event')
        ->assertSee('Daftarkan event Anda di')
        ->assertSee('Daftar sebagai Organizer')
        ->assertSee(route('register', ['intent' => 'organizer']), false)
        ->assertSee('Ajukan review & tayang');
});

it('shows the organizer call to action on the landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Punya event? Daftarkan event Anda di sini.')
        ->assertSee(route('register', ['intent' => 'organizer']), false)
        ->assertSee(route('organizer.guide'), false);
});

it('shows onboarding steps to an unverified organizer', function () {
    $user = User::factory()->organizer()->create();
    Organizer::factory()->forOwner($user)->unverified()->create();

    $this->actingAs($user)
        ->get(route('organizer.dashboard'))
        ->assertOk()
        ->assertSee('Langkah menjadi organizer')
        ->assertSee('Menunggu verifikasi admin')
        ->assertSee('Baca panduan');
});

it('shows event registration guidance to a verified organizer without events', function () {
    $user = User::factory()->organizer()->create();
    Organizer::factory()->forOwner($user)->create();

    $this->actingAs($user)
        ->get(route('organizer.dashboard'))
        ->assertOk()
        ->assertSee('Mulai daftarkan event pertama')
        ->assertSee('Buat event draft')
        ->assertSee(route('organizer.events.create'), false);
});

it('links the guide from the organizer registration page', function () {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->get(route('organizer.register'))
        ->assertOk()
        ->assertSee('Cara mendaftarkan event')
        ->assertSee('Anda bisa mendaftarkan event di sini')
        ->assertSee(route('organizer.guide'), false);
});
