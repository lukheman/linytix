<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\OrganizerSlugRedirect;
use App\Models\User;

/**
 * @return array{0: User, 1: Organizer}
 */
function organizerOwner(array $organizerOverrides = []): array
{
    $user = User::factory()->organizer()->create();

    $organizer = Organizer::factory()
        ->forOwner($user)
        ->create(array_merge([
            'name' => 'Nusa Production',
            'slug' => 'nusa-production',
            'contact_email' => 'hello@nusa.test',
            'contact_phone' => '081200000000',
        ], $organizerOverrides));

    return [$user, $organizer];
}

function organizerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nusa Production Baru',
        'slug' => 'nusa-production-baru',
        'description' => 'Penyelenggara konser dan festival.',
        'logo_url' => 'https://cdn.example.test/logo.png',
        'contact_email' => 'halo@nusa.test',
        'contact_phone' => '081211112222',
        'bank_name' => 'Bank BCA',
        'bank_account_no' => '1234567890123',
        'bank_account_name' => 'PT Nusa Production',
    ], $overrides);
}

it('lets an organizer owner update the profile and records an audit log', function () {
    [$user, $organizer] = organizerOwner();

    $this->actingAs($user)
        ->get(route('organizer.profile'))
        ->assertOk()
        ->assertSee('Nusa Production');

    $this->actingAs($user)
        ->put(route('organizer.profile.update'), organizerPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $organizer->refresh();

    expect($organizer->name)->toBe('Nusa Production Baru')
        ->and($organizer->slug)->toBe('nusa-production-baru')
        ->and($organizer->contact_email)->toBe('halo@nusa.test')
        ->and($organizer->bank_account_no)->toBe('1234567890123');

    $log = AuditLog::query()->where('action', 'organizer.updated')->firstOrFail();

    expect($log->entity)->toBe('organizer')
        ->and($log->entity_id)->toBe($organizer->getKey())
        ->and($log->meta['changes']['slug']['from'])->toBe('nusa-production')
        ->and($log->meta['changes']['slug']['to'])->toBe('nusa-production-baru')
        ->and($log->meta['changes']['bank_account_no']['to'])->toBe('****0123')
        ->and(json_encode($log->meta))->not->toContain('1234567890123');
});

it('rejects a slug that belongs to another organizer', function () {
    [$user, $organizer] = organizerOwner();

    Organizer::factory()->create(['slug' => 'already-used']);

    $this->actingAs($user)
        ->put(route('organizer.profile.update'), organizerPayload(['slug' => 'already-used']))
        ->assertSessionHasErrors('slug');

    expect($organizer->fresh()->slug)->toBe('nusa-production');
});

it('creates a 301 redirect from the old slug when the organizer has public events', function () {
    [$user, $organizer] = organizerOwner();

    Event::factory()
        ->forOrganizer($organizer)
        ->create([
            'status' => Event::STATUS_PUBLISHED,
            'start_at' => now()->addDays(7),
            'end_at' => now()->addDays(7)->addHours(3),
        ]);

    $this->actingAs($user)
        ->put(route('organizer.profile.update'), organizerPayload(['slug' => 'nusa-baru']))
        ->assertRedirect()
        ->assertSessionHas('success');

    $redirect = OrganizerSlugRedirect::query()->where('slug', 'nusa-production')->firstOrFail();

    expect($redirect->organizer_id)->toBe($organizer->getKey());

    $this->get(route('organizers.show', 'nusa-production'))
        ->assertStatus(301)
        ->assertRedirect(route('organizers.show', 'nusa-baru'));

    $this->get(route('organizers.show', 'nusa-baru'))
        ->assertOk()
        ->assertSee('Nusa Production Baru');
});

it('does not create a slug redirect when the organizer has no public event', function () {
    [$user, $organizer] = organizerOwner();

    Event::factory()
        ->forOrganizer($organizer)
        ->draft()
        ->create();

    $this->actingAs($user)
        ->put(route('organizer.profile.update'), organizerPayload(['slug' => 'nusa-draft-only']))
        ->assertRedirect();

    expect(OrganizerSlugRedirect::query()->where('slug', 'nusa-production')->exists())->toBeFalse();
});

it('prevents non owners from updating an organizer profile', function () {
    [$owner, $organizer] = organizerOwner();

    $otherOrganizer = User::factory()->organizer()->create();
    Organizer::factory()->forOwner($otherOrganizer)->create();

    // Organizer lain hanya dapat mengubah profilnya sendiri, bukan milik owner.
    $this->actingAs($otherOrganizer)
        ->put(route('organizer.profile.update'), organizerPayload())
        ->assertRedirect();

    expect($organizer->fresh()->name)->toBe('Nusa Production');

    $this->actingAs(User::factory()->customer()->create())
        ->get(route('organizer.profile'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('organizer.profile'))
        ->assertOk();
});

it('shows the organizer dashboard to an organizer owner', function () {
    [$user, $organizer] = organizerOwner();

    Event::factory()->forOrganizer($organizer)->draft()->create(['title' => 'Konser Draft']);

    $this->actingAs($user)
        ->get(route('organizer.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Organizer')
        ->assertSee('Konser Draft')
        ->assertSee('Nusa Production');
});

it('shows organizers with masked bank accounts to admins', function () {
    [, $organizer] = organizerOwner([
        'bank_name' => 'Bank BCA',
        'bank_account_no' => '1234567890123',
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.organizers'))
        ->assertOk()
        ->assertSee('Nusa Production')
        ->assertSee('****0123')
        ->assertDontSee('1234567890123');
});

it('lets an admin verify an organizer with a note', function () {
    [, $organizer] = organizerOwner(['is_verified' => false, 'verified_at' => null]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.organizers.verify', $organizer), [
            'action' => 'verify',
            'verification_note' => 'Dokumen legal lengkap.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $organizer->refresh();

    expect($organizer->is_verified)->toBeTrue()
        ->and($organizer->verified_at)->not->toBeNull()
        ->and($organizer->verified_by)->toBe($admin->getKey())
        ->and($organizer->verification_note)->toBe('Dokumen legal lengkap.')
        ->and(AuditLog::query()->where('action', 'organizer.verified')->count())->toBe(1);

    $this->actingAs($admin)
        ->post(route('admin.organizers.verify', $organizer), [
            'action' => 'unverify',
            'verification_note' => 'Dokumen perlu diperbarui.',
        ])
        ->assertRedirect();

    $organizer->refresh();

    expect($organizer->is_verified)->toBeFalse()
        ->and($organizer->verified_at)->toBeNull()
        ->and($organizer->verified_by)->toBeNull()
        ->and($organizer->verification_note)->toBe('Dokumen perlu diperbarui.')
        ->and(AuditLog::query()->where('action', 'organizer.unverified')->count())->toBe(1);
});

it('prevents non admins from verifying organizers', function () {
    [$user, $organizer] = organizerOwner(['is_verified' => false, 'verified_at' => null]);

    $this->actingAs($user)
        ->post(route('admin.organizers.verify', $organizer), ['action' => 'verify'])
        ->assertForbidden();

    expect($organizer->fresh()->is_verified)->toBeFalse();
});

it('auto creates an organizer profile for an organizer user', function () {
    $user = User::factory()->organizer()->create(['name' => 'Penyelenggara Baru']);

    $this->actingAs($user)
        ->get(route('organizer.profile'))
        ->assertOk()
        ->assertSee('Penyelenggara Baru');

    expect(Organizer::query()->where('owner_user_id', $user->getKey())->exists())->toBeTrue();
});

it('blocks unverified organizers from submitting events for review', function () {
    [$user, $organizer] = organizerOwner(['is_verified' => false, 'verified_at' => null]);

    $event = Event::factory()->forOrganizer($organizer)->draft()->create();

    expect($user->can('submitForReview', $event))->toBeFalse();

    $organizer->forceFill(['is_verified' => true, 'verified_at' => now()])->save();

    expect($user->fresh()->can('submitForReview', $event->fresh()))->toBeTrue();
});
