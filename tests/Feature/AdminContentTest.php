<?php

use App\Models\SiteSetting;
use App\Models\User;

it('protects ticketing settings behind authentication', function () {
    $this->get(route('admin.settings'))->assertRedirect(route('login'));
});

it('allows admin to view ticketing settings', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Pengaturan Ticketing')
        ->assertSee('Identitas & Beranda Ticketing');
});

it('blocks non admins from viewing or changing ticketing settings', function () {
    $customer = User::factory()->customer()->create();
    $organizer = User::factory()->organizer()->create();

    $this->actingAs($customer)->get(route('admin.settings'))->assertForbidden();
    $this->actingAs($organizer)->get(route('admin.settings'))->assertForbidden();

    $this->actingAs($customer)
        ->put(route('admin.settings.update'), ['site_name' => 'Diambil Alih'])
        ->assertForbidden();

    expect(SiteSetting::query()->where('key', 'site_name')->exists())->toBeFalse();
});
