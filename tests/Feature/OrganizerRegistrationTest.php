<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketType;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function organizerApplication(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nusa Production',
        'description' => 'Penyelenggara konser dan festival.',
        'contact_email' => 'halo@nusa.test',
        'contact_phone' => '081211112222',
        'agree_terms' => '1',
    ], $overrides);
}

function verifiedCustomer(array $attributes = []): User
{
    return User::factory()->customer()->create($attributes);
}

it('redirects guests to login from the organizer registration page', function () {
    $this->get(route('organizer.register'))->assertRedirect(route('login'));
    $this->post(route('organizer.register.store'), organizerApplication())->assertRedirect(route('login'));
});

it('requires a verified email before applying as an organizer', function () {
    $user = User::factory()->customer()->unverified()->create();

    $this->actingAs($user)
        ->get(route('organizer.register'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->post(route('organizer.register.store'), organizerApplication())
        ->assertRedirect(route('verification.notice'));

    expect(Organizer::query()->count())->toBe(0);
});

it('shows the organizer application form to a verified customer', function () {
    $user = verifiedCustomer(['name' => 'Budi Santoso', 'phone' => '081200000000']);

    $this->actingAs($user)
        ->get(route('organizer.register'))
        ->assertOk()
        ->assertSee('Daftar sebagai organizer')
        ->assertSee('Cara mendaftarkan event')
        ->assertSee('Anda bisa mendaftarkan event di sini')
        ->assertSee(route('organizer.guide'), false)
        ->assertSee('Budi Santoso')
        ->assertSee('081200000000');
});

it('creates an unverified organizer, assigns the organizer role, and records an audit log', function () {
    $user = verifiedCustomer(['email' => 'budi@example.com']);

    $this->actingAs($user)
        ->post(route('organizer.register.store'), organizerApplication())
        ->assertRedirect(route('organizer.dashboard'))
        ->assertSessionHas('status');

    $organizer = Organizer::query()->where('owner_user_id', $user->getKey())->firstOrFail();

    expect($organizer->name)->toBe('Nusa Production')
        ->and($organizer->slug)->toBe('nusa-production')
        ->and($organizer->is_verified)->toBeFalse()
        ->and($organizer->verified_at)->toBeNull()
        ->and($organizer->contact_email)->toBe('halo@nusa.test')
        ->and($organizer->contact_phone)->toBe('081211112222')
        ->and($user->fresh()->hasRole(Role::ORGANIZER))->toBeTrue()
        ->and($user->fresh()->hasRole(Role::CUSTOMER))->toBeTrue()
        ->and(AuditLog::query()->where('action', 'organizer.registered')->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('organizer.dashboard'))
        ->assertOk()
        ->assertSee('Menunggu verifikasi admin');
});

it('does not create a second organizer when the application is submitted twice', function () {
    $user = verifiedCustomer();

    $this->actingAs($user)
        ->post(route('organizer.register.store'), organizerApplication())
        ->assertRedirect(route('organizer.dashboard'));

    $this->actingAs($user)
        ->post(route('organizer.register.store'), organizerApplication(['name' => 'Nama Lain']))
        ->assertRedirect(route('organizer.dashboard'));

    expect(Organizer::query()->where('owner_user_id', $user->getKey())->count())->toBe(1)
        ->and(Organizer::query()->firstOrFail()->name)->toBe('Nusa Production');
});

it('redirects existing organizers to their dashboard instead of the form', function () {
    $user = User::factory()->organizer()->create();

    $this->actingAs($user)
        ->get(route('organizer.register'))
        ->assertRedirect(route('organizer.dashboard'));

    $this->actingAs($user)
        ->post(route('organizer.register.store'), organizerApplication())
        ->assertRedirect(route('organizer.dashboard'));
});

it('validates the organizer application fields', function () {
    $user = verifiedCustomer();

    $this->actingAs($user)
        ->post(route('organizer.register.store'), [
            'name' => '',
            'contact_email' => 'not-an-email',
            'contact_phone' => '',
        ])
        ->assertSessionHasErrors(['name', 'contact_email', 'contact_phone', 'agree_terms']);

    expect(Organizer::query()->count())->toBe(0);
});

it('generates a unique slug when two organizers share the same name', function () {
    $first = verifiedCustomer();
    $second = verifiedCustomer();

    $this->actingAs($first)->post(route('organizer.register.store'), organizerApplication());
    $this->actingAs($second)->post(route('organizer.register.store'), organizerApplication());

    $slugs = Organizer::query()->orderBy('created_at')->pluck('slug')->all();

    expect($slugs)->toHaveCount(2)
        ->and($slugs[0])->toBe('nusa-production')
        ->and($slugs[1])->toBe('nusa-production-2');
});

it('ignores role input from the applicant', function () {
    $user = verifiedCustomer();

    $this->actingAs($user)->post(route('organizer.register.store'), organizerApplication([
        'role' => Role::ADMIN,
        'is_verified' => true,
    ]));

    $user->refresh();

    expect($user->isAdmin())->toBeFalse()
        ->and($user->hasRole(Role::ORGANIZER))->toBeTrue()
        ->and(Organizer::query()->firstOrFail()->is_verified)->toBeFalse();
});

it('throttles repeated organizer applications', function () {
    $user = verifiedCustomer();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->actingAs($user)->post(route('organizer.register.store'), organizerApplication());
    }

    $this->actingAs($user)
        ->post(route('organizer.register.store'), organizerApplication())
        ->assertStatus(429);
});

it('shows pending applications to admins and lets them verify one', function () {
    $user = verifiedCustomer();
    $this->actingAs($user)->post(route('organizer.register.store'), organizerApplication());

    $organizer = Organizer::query()->firstOrFail();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.organizers', ['status' => 'unverified']))
        ->assertOk()
        ->assertSee('pengajuan organizer')
        ->assertSee('Menunggu verifikasi')
        ->assertSee('Nusa Production');

    $this->actingAs($admin)
        ->post(route('admin.organizers.verify', $organizer), [
            'action' => 'verify',
            'verification_note' => 'Kontak dan rekening valid.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($organizer->fresh()->is_verified)->toBeTrue()
        ->and($organizer->fresh()->verified_by)->toBe($admin->getKey())
        ->and(AuditLog::query()->where('action', 'organizer.verified')->count())->toBe(1);
});

it('requires a note when rejecting and shows the note to the organizer', function () {
    $user = verifiedCustomer();
    $this->actingAs($user)->post(route('organizer.register.store'), organizerApplication());

    $organizer = Organizer::query()->firstOrFail();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.organizers.verify', $organizer), ['action' => 'reject'])
        ->assertSessionHasErrors('verification_note');

    expect($organizer->fresh()->verification_note)->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.organizers.verify', $organizer), [
            'action' => 'reject',
            'verification_note' => 'Nomor telepon tidak dapat dihubungi.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $organizer->refresh();

    expect($organizer->is_verified)->toBeFalse()
        ->and($organizer->verified_at)->toBeNull()
        ->and($organizer->verified_by)->toBeNull()
        ->and($organizer->verification_note)->toBe('Nomor telepon tidak dapat dihubungi.')
        ->and(AuditLog::query()->where('action', 'organizer.rejected')->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('organizer.profile'))
        ->assertOk()
        ->assertSee('Perlu perbaikan')
        ->assertSee('Nomor telepon tidak dapat dihubungi.');
});

it('allows submitting an event for review only after the organizer is verified', function () {
    $user = verifiedCustomer();
    $this->actingAs($user)->post(route('organizer.register.store'), organizerApplication());

    $organizer = Organizer::query()->firstOrFail();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create([
        'poster_url' => 'https://cdn.example.test/poster.jpg',
    ]);
    TicketType::factory()->forEvent($event)->create(['is_active' => true]);

    $this->actingAs($user)
        ->post(route('organizer.events.submit', $event))
        ->assertForbidden();

    expect($event->fresh()->status)->toBe(Event::STATUS_DRAFT);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.organizers.verify', $organizer), [
        'action' => 'verify',
        'verification_note' => 'Profil lengkap.',
    ]);

    $this->actingAs($user)
        ->post(route('organizer.events.submit', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW);
});

it('blocks inactive users from applying as organizers', function () {
    $user = verifiedCustomer();
    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)
        ->get(route('organizer.register'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('organizer.register.store'), organizerApplication())
        ->assertForbidden();

    expect(Organizer::query()->count())->toBe(0);
});
