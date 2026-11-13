<?php

use App\Models\Organizer;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Validation\ValidationException;

it('lets an admin search users and filter by role and status', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Admin Utama',
        'email' => 'admin-utama@example.test',
    ]);

    $organizer = User::factory()->organizer()->create([
        'name' => 'Organizer Musik',
        'email' => 'organizer-musik@example.test',
    ]);

    $staff = User::factory()->staff()->create([
        'name' => 'Staff Lapangan',
        'email' => 'staff-lapangan@example.test',
    ]);

    $inactive = User::factory()->customer()->inactive()->create([
        'name' => 'Customer Nonaktif',
        'email' => 'customer-nonaktif@example.test',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users', ['q' => 'Organizer Musik']))
        ->assertOk()
        ->assertSee('organizer-musik@example.test')
        ->assertDontSee('staff-lapangan@example.test');

    $this->actingAs($admin)
        ->get(route('admin.users', ['role' => 'organizer']))
        ->assertOk()
        ->assertSee('organizer-musik@example.test')
        ->assertDontSee('staff-lapangan@example.test');

    $this->actingAs($admin)
        ->get(route('admin.users', ['status' => 'inactive']))
        ->assertOk()
        ->assertSee('customer-nonaktif@example.test')
        ->assertDontSee('organizer-musik@example.test')
        ->assertDontSee('admin-utama@example.test');

    $this->actingAs($admin)
        ->get(route('admin.users', [
            'q' => 'customer',
            'role' => 'customer',
            'status' => 'inactive',
        ]))
        ->assertOk()
        ->assertSee('customer-nonaktif@example.test')
        ->assertDontSee('staff-lapangan@example.test');
});

it('deactivates and reactivates a user and blocks inactive users from logging in', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->customer()->create(['email' => 'user-status@example.test']);

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->patch(route('admin.users.status', $user), ['is_active' => '0'])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success');

    expect($user->fresh()->is_active)->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'user.deactivated',
        'entity' => 'user',
        'entity_id' => $user->getKey(),
        'user_id' => $admin->getKey(),
    ]);

    $this->post('/logout');
    $this->assertGuest();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->patch(route('admin.users.status', $user), ['is_active' => '1'])
        ->assertRedirect(route('admin.users'));

    expect($user->fresh()->is_active)->toBeTrue();

    $this->post('/logout');
    $this->assertGuest();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/akun');

    $this->assertAuthenticatedAs($user->fresh());

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'user.activated',
        'entity_id' => $user->getKey(),
    ]);
});

it('prevents an admin from deactivating or deleting themselves', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->patch(route('admin.users.status', $admin), ['is_active' => '0'])
        ->assertSessionHasErrors('status');

    expect($admin->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin)
        ->from(route('admin.users'))
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors('user');

    expect(User::find($admin->getKey()))->not->toBeNull()
        ->and($otherAdmin->fresh()->is_active)->toBeTrue();
});

it('prevents deactivating the last active admin', function () {
    $actor = User::factory()->admin()->create();
    $lastAdmin = User::factory()->admin()->create();

    // Simulasikan kondisi tidak ada admin aktif lain selain target.
    $actor->forceFill(['is_active' => false])->save();

    expect(fn () => app(UserManagementService::class)->setActive(
        $lastAdmin,
        false,
        $actor->fresh(),
    ))->toThrow(ValidationException::class);

    expect($lastAdmin->fresh()->is_active)->toBeTrue();
});

it('audits user creation, update, and deletion', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Audit User',
        'email' => 'audit-user@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('admin.users'));

    $user = User::where('email', 'audit-user@example.test')->firstOrFail();

    expect($user->isCustomer())->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'user.created',
        'entity_id' => $user->getKey(),
    ]);

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'Audit User Updated',
        'email' => 'audit-user@example.test',
    ])->assertRedirect(route('admin.users'));

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'user.updated',
        'entity_id' => $user->getKey(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users'));

    $this->assertDatabaseMissing('users', ['id' => $user->getKey()]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'user.deleted',
        'entity_id' => $user->getKey(),
    ]);
});

it('restricts user management to admins', function () {
    $owner = User::factory()->organizer()->create();

    Organizer::factory()->forOwner($owner)->create(['slug' => 'organizer-akses']);

    $this->actingAs($owner)
        ->get(route('admin.users'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('admin.organizers'))
        ->assertForbidden();
});

it('lets an admin verify an organizer and records the action', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->organizer()->create();

    $organizer = Organizer::factory()->forOwner($owner)->create([
        'name' => 'Organizer Verifikasi',
        'slug' => 'organizer-verifikasi',
        'is_verified' => false,
        'verified_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.organizers', ['status' => 'unverified']))
        ->assertOk()
        ->assertSee('Organizer Verifikasi')
        ->assertSee('Belum terverifikasi');

    $this->actingAs($admin)
        ->post(route('admin.organizers.verify', $organizer), [
            'action' => 'verify',
            'verification_note' => 'Dokumen lengkap.',
        ])
        ->assertRedirect();

    $organizer->refresh();

    expect($organizer->is_verified)->toBeTrue()
        ->and($organizer->verified_by)->toBe($admin->getKey())
        ->and($organizer->verification_note)->toBe('Dokumen lengkap.');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'organizer.verified',
        'entity' => 'organizer',
        'entity_id' => $organizer->getKey(),
        'user_id' => $admin->getKey(),
    ]);
});
