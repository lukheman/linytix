<?php

use App\Models\User;

it('protects ticketing settings behind authentication', function () {
    $this->get(route('admin.settings'))->assertRedirect(route('login'));
});

it('allows admin to view ticketing settings', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Pengaturan Ticketing')
        ->assertSee('Identitas & Beranda Ticketing');
});
