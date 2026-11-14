<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventStaffInvitation;
use App\Models\Organizer;
use App\Models\User;
use App\Notifications\StaffAssigned;
use App\Notifications\StaffInvitation;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * @return array{0: User, 1: Organizer, 2: Event}
 */
function staffInvitationContext(array $eventOverrides = []): array
{
    $owner = User::factory()->organizer()->create();

    $organizer = Organizer::factory()
        ->forOwner($owner)
        ->create([
            'name' => 'Nusa Undangan',
            'slug' => 'nusa-undangan',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->create(array_merge([
            'title' => 'Konser Undangan Nusa',
            'status' => Event::STATUS_PUBLISHED,
            'published_at' => now(),
            'start_at' => now()->addDays(7),
        ], $eventOverrides));

    return [$owner, $organizer, $event];
}

/**
 * @return array{0: EventStaffInvitation, 1: string}
 */
function pendingInvitation(Event $event, string $email = 'baru@example.test', string $gate = 'Gate A'): array
{
    $token = Str::random(64);

    $invitation = EventStaffInvitation::factory()
        ->withToken($token)
        ->create([
            'event_id' => $event->getKey(),
            'email' => $email,
            'gate' => $gate,
        ]);

    return [$invitation, $token];
}

it('invites an unknown email and sends an invitation link', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.store', $event), [
            'email' => 'Baru@Example.Test',
            'gate' => 'Gate A',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $invitation = EventStaffInvitation::query()->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->email)->toBe('baru@example.test')
        ->and($invitation->gate)->toBe('Gate A')
        ->and($invitation->isPending())->toBeTrue()
        ->and($invitation->token_hash)->toHaveLength(64);

    expect(AuditLog::query()->where('action', 'event.staff.invited')->count())->toBe(1);

    Notification::assertSentOnDemand(
        StaffInvitation::class,
        fn (StaffInvitation $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'baru@example.test',
    );
});

it('assigns an existing active user directly and emails them', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();
    $staff = User::factory()->create(['email' => 'lama@example.test']);

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.store', $event), [
            'email' => 'lama@example.test',
            'gate' => 'Gate VIP',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->staff()->whereKey($staff->getKey())->exists())->toBeTrue()
        ->and($staff->fresh()->isStaff())->toBeTrue()
        ->and(EventStaffInvitation::query()->count())->toBe(0);

    Notification::assertSentTo($staff, StaffAssigned::class);
});

it('does not email again when an existing staff keeps the same gate', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();
    $staff = User::factory()->staff()->create(['email' => 'lama@example.test']);
    $event->staff()->attach($staff->getKey(), ['gate' => 'Gate A']);

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.store', $event), [
            'email' => 'lama@example.test',
            'gate' => 'Gate A',
        ])
        ->assertSessionHas('success');

    Notification::assertNothingSent();
});

it('rejects inviting an inactive account or the organizer owner', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();
    $inactive = User::factory()->inactive()->create(['email' => 'nonaktif@example.test']);

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.store', $event), [
            'email' => 'nonaktif@example.test',
            'gate' => 'Gate A',
        ])
        ->assertSessionHasErrors('email');

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.store', $event), [
            'email' => $owner->email,
            'gate' => 'Gate A',
        ])
        ->assertSessionHasErrors('email');

    expect($event->staff()->whereKey($inactive->getKey())->exists())->toBeFalse()
        ->and(EventStaffInvitation::query()->count())->toBe(0);
});

it('rejects a duplicate pending invitation for the same email', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();
    pendingInvitation($event, 'baru@example.test');

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.store', $event), [
            'email' => 'baru@example.test',
            'gate' => 'Gate B',
        ])
        ->assertSessionHasErrors('email');

    expect(EventStaffInvitation::query()->count())->toBe(1);
});

it('rotates the token when an invitation is resent', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();
    [$invitation, $oldToken] = pendingInvitation($event);
    $invitation->forceFill(['last_sent_at' => now()->subMinutes(5)])->save();

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.resend', [$event, $invitation]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $fresh = $invitation->fresh();

    expect($fresh->token_hash)->not->toBe(EventStaffInvitation::hashToken($oldToken))
        ->and($fresh->send_count)->toBe(2)
        ->and(EventStaffInvitation::findByToken($oldToken))->toBeNull();

    Notification::assertSentOnDemand(StaffInvitation::class);
});

it('enforces the resend cooldown', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();
    [$invitation] = pendingInvitation($event);
    $invitation->forceFill(['last_sent_at' => now()])->save();

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.resend', [$event, $invitation]))
        ->assertSessionHasErrors('email');

    expect($invitation->fresh()->send_count)->toBe(1);
});

it('revokes a pending invitation and invalidates its token', function () {
    Notification::fake();

    [$owner, , $event] = staffInvitationContext();
    [$invitation, $token] = pendingInvitation($event);

    $this->actingAs($owner)
        ->delete(route('organizer.events.staff.invitations.destroy', [$event, $invitation]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($invitation->fresh()->isRevoked())->toBeTrue()
        ->and($invitation->fresh()->isPending())->toBeFalse();

    $this->get(route('staff.invitations.show', $token))
        ->assertOk()
        ->assertSee('telah dibatalkan');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'event.staff.invitation_revoked',
        'entity_id' => $event->getKey(),
    ]);
});

it('shows the invitation page with event and gate details', function () {
    [, , $event] = staffInvitationContext();
    [, $token] = pendingInvitation($event, 'baru@example.test', 'Gate Utama');

    $this->get(route('staff.invitations.show', $token))
        ->assertOk()
        ->assertSee($event->title)
        ->assertSee('Gate Utama')
        ->assertSee('baru@example.test')
        ->assertSee('Buat akun');
});

it('shows an expired invitation without an accept action', function () {
    [, , $event] = staffInvitationContext();
    $token = Str::random(64);

    EventStaffInvitation::factory()
        ->withToken($token)
        ->expired()
        ->create([
            'event_id' => $event->getKey(),
            'email' => 'baru@example.test',
        ]);

    $this->get(route('staff.invitations.show', $token))
        ->assertOk()
        ->assertSee('kedaluwarsa')
        ->assertDontSee('Buat akun &amp; terima undangan', false);
});

it('returns 404 for an unknown invitation token', function () {
    $this->get(route('staff.invitations.show', Str::random(64)))->assertNotFound();
});

it('lets a matching user accept an invitation and grants the staff role', function () {
    [, , $event] = staffInvitationContext();
    [$invitation, $token] = pendingInvitation($event, 'baru@example.test', 'Gate B');

    $staff = User::factory()->create(['email' => 'baru@example.test']);

    $this->actingAs($staff)
        ->post(route('staff.invitations.accept', $token))
        ->assertRedirect(route('scan.index'));

    expect($event->staff()->whereKey($staff->getKey())->exists())->toBeTrue()
        ->and($staff->fresh()->isStaff())->toBeTrue()
        ->and($invitation->fresh()->isAccepted())->toBeTrue()
        ->and($invitation->fresh()->accepted_by)->toBe($staff->getKey());

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'event.staff.invitation_accepted',
        'entity_id' => $event->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $this->actingAs($staff)
        ->get(route('scan.index'))
        ->assertOk()
        ->assertSee($event->title);
});

it('rejects accepting an invitation with a different email', function () {
    [, , $event] = staffInvitationContext();
    [, $token] = pendingInvitation($event, 'baru@example.test');

    $other = User::factory()->create(['email' => 'lain@example.test']);

    $this->actingAs($other)
        ->post(route('staff.invitations.accept', $token))
        ->assertSessionHasErrors('invitation');

    expect($event->staff()->whereKey($other->getKey())->exists())->toBeFalse();
});

it('does not allow an invitation token to be used twice', function () {
    [, , $event] = staffInvitationContext();
    [, $token] = pendingInvitation($event, 'baru@example.test');

    $staff = User::factory()->create(['email' => 'baru@example.test']);

    $this->actingAs($staff)
        ->post(route('staff.invitations.accept', $token))
        ->assertRedirect(route('scan.index'));

    $this->actingAs($staff)
        ->post(route('staff.invitations.accept', $token))
        ->assertSessionHasErrors('invitation');
});

it('blocks invitations for a cancelled event', function () {
    Notification::fake();

    [$owner, $organizer] = staffInvitationContext();
    $event = Event::factory()->forOrganizer($organizer)->create([
        'status' => Event::STATUS_CANCELLED,
    ]);

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.invitations.store', $event), [
            'email' => 'baru@example.test',
            'gate' => 'Gate A',
        ])
        ->assertSessionHasErrors('email');

    expect(EventStaffInvitation::query()->count())->toBe(0);
});

it('blocks accepting an invitation after the event is cancelled', function () {
    [, , $event] = staffInvitationContext();
    [, $token] = pendingInvitation($event, 'baru@example.test');

    $event->forceFill(['status' => Event::STATUS_CANCELLED])->save();

    $staff = User::factory()->create(['email' => 'baru@example.test']);

    $this->actingAs($staff)
        ->post(route('staff.invitations.accept', $token))
        ->assertSessionHasErrors('invitation');

    expect($event->staff()->whereKey($staff->getKey())->exists())->toBeFalse();
});

it('lets an admin invite staff from the admin event page', function () {
    Notification::fake();

    [, , $event] = staffInvitationContext();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.staff.invitations.store', $event), [
            'email' => 'baru@example.test',
            'gate' => 'Gate A',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(EventStaffInvitation::query()->count())->toBe(1);
});

it('shows pending invitations on the staff management page', function () {
    [$owner, , $event] = staffInvitationContext();
    pendingInvitation($event, 'menunggu@example.test', 'Gate C');

    $this->actingAs($owner)
        ->get(route('organizer.events.staff.index', $event))
        ->assertOk()
        ->assertSee('Undangan Menunggu')
        ->assertSee('menunggu@example.test')
        ->assertSee('Gate C');
});

it('registers a new staff through an invitation and assigns them', function () {
    Notification::fake();

    [, , $event] = staffInvitationContext();
    [, $token] = pendingInvitation($event, 'baru@example.test', 'Gate B');

    $this->post(route('register.store'), [
        'invitation' => $token,
        'name' => 'Staff Baru',
        'email' => 'baru@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
    ])->assertRedirect(route('scan.index'));

    $user = User::query()->where('email', 'baru@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->isStaff())->toBeTrue()
        ->and($event->staff()->whereKey($user->getKey())->exists())->toBeTrue()
        ->and(EventStaffInvitation::findByToken($token)->isAccepted())->toBeTrue();

    $this->actingAs($user)
        ->get(route('scan.index'))
        ->assertOk()
        ->assertSee($event->title)
        ->assertSee('Gate B');
});

it('ignores a different email when registering through an invitation', function () {
    Notification::fake();

    [, , $event] = staffInvitationContext();
    [, $token] = pendingInvitation($event, 'baru@example.test');

    $this->post(route('register.store'), [
        'invitation' => $token,
        'name' => 'Staff Baru',
        'email' => 'lain@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
    ])->assertRedirect(route('scan.index'));

    expect(User::query()->where('email', 'baru@example.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'lain@example.test')->exists())->toBeFalse();
});

it('redirects registered invitation recipients to login instead of registration', function () {
    [, , $event] = staffInvitationContext();
    User::factory()->create(['email' => 'sudah@example.test']);
    [, $token] = pendingInvitation($event, 'sudah@example.test');

    $this->get(route('register', ['invitation' => $token]))
        ->assertRedirect(route('login', [
            'redirect' => route('staff.invitations.show', $token),
        ]));
});

it('rejects registration with an expired invitation token', function () {
    [, , $event] = staffInvitationContext();
    $token = Str::random(64);

    EventStaffInvitation::factory()
        ->withToken($token)
        ->expired()
        ->create([
            'event_id' => $event->getKey(),
            'email' => 'baru@example.test',
        ]);

    $this->post(route('register.store'), [
        'invitation' => $token,
        'name' => 'Staff Baru',
        'email' => 'baru@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'agree_terms' => '1',
    ])->assertSessionHasErrors('invitation');

    expect(User::query()->where('email', 'baru@example.test')->exists())->toBeFalse();
});

it('builds the invitation email with event and gate details', function () {
    [, , $event] = staffInvitationContext();
    [$invitation, $token] = pendingInvitation($event, 'baru@example.test', 'Gate A');

    $mail = (new StaffInvitation($invitation, route('staff.invitations.show', $token)))
        ->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain($event->title)
        ->and(implode(' ', $mail->introLines))->toContain('Gate A');
});

it('builds the assigned staff email with a scan link', function () {
    [, , $event] = staffInvitationContext();
    $staff = User::factory()->create(['email' => 'lama@example.test']);

    $mail = (new StaffAssigned($event, 'Gate VIP'))->toMail($staff);

    expect($mail->subject)->toContain($event->title)
        ->and(implode(' ', $mail->introLines))->toContain('Gate VIP')
        ->and($mail->actionUrl)->toBe(route('scan.index'));
});
