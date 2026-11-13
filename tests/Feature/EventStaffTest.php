<?php

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * @return array{0: User, 1: Organizer, 2: Event}
 */
function eventStaffContext(): array
{
    $owner = User::factory()->organizer()->create();

    $organizer = Organizer::factory()
        ->forOwner($owner)
        ->create([
            'name' => 'Nusa Staff',
            'slug' => 'nusa-staff',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->create([
            'title' => 'Konser Staff Nusa',
            'status' => Event::STATUS_PUBLISHED,
            'published_at' => now(),
            'start_at' => now()->addDays(7),
        ]);

    return [$owner, $organizer, $event];
}

it('lets an admin assign and remove staff with a gate', function () {
    [$owner, , $event] = eventStaffContext();

    $admin = User::factory()->admin()->create();
    $staff = User::factory()->create(['email' => 'petugas@example.test']);

    $this->actingAs($admin)
        ->get(route('admin.events.staff.index', $event))
        ->assertOk()
        ->assertSee('Kelola Staff')
        ->assertSee($event->title);

    $this->actingAs($admin)
        ->post(route('admin.events.staff.store', $event), [
            'email' => 'petugas@example.test',
            'gate' => 'Gate A',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('event_staff', [
        'event_id' => $event->getKey(),
        'user_id' => $staff->getKey(),
        'gate' => 'Gate A',
    ]);

    expect($staff->fresh()->isStaff())->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'event.staff.assigned',
        'entity' => 'event',
        'entity_id' => $event->getKey(),
        'user_id' => $admin->getKey(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.events.staff.destroy', [$event, $staff]))
        ->assertRedirect();

    $this->assertDatabaseMissing('event_staff', [
        'event_id' => $event->getKey(),
        'user_id' => $staff->getKey(),
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'event.staff.removed',
        'entity_id' => $event->getKey(),
    ]);

    // Pemilik organizer tidak perlu ditugaskan sebagai staff.
    $this->actingAs($admin)
        ->post(route('admin.events.staff.store', $event), [
            'email' => $owner->email,
            'gate' => 'Gate B',
        ])
        ->assertSessionHasErrors('email');
});

it('rejects assigning an unknown or inactive user', function () {
    [, , $event] = eventStaffContext();

    $admin = User::factory()->admin()->create();
    $inactive = User::factory()->inactive()->create(['email' => 'nonaktif@example.test']);

    $this->actingAs($admin)
        ->post(route('admin.events.staff.store', $event), [
            'email' => 'tidak-ada@example.test',
            'gate' => 'Gate A',
        ])
        ->assertSessionHasErrors('email');

    $this->actingAs($admin)
        ->post(route('admin.events.staff.store', $event), [
            'email' => $inactive->email,
            'gate' => 'Gate A',
        ])
        ->assertSessionHasErrors('email');
});

it('lets an organizer manage staff only for their own event', function () {
    [$owner, , $event] = eventStaffContext();

    $staff = User::factory()->create(['email' => 'staff-organizer@example.test']);

    $other = User::factory()->organizer()->create();
    Organizer::factory()->forOwner($other)->create([
        'slug' => 'organizer-lain-staff',
        'is_verified' => true,
    ]);

    $this->actingAs($other)
        ->get(route('organizer.events.staff.index', $event))
        ->assertForbidden();

    $this->actingAs($other)
        ->post(route('organizer.events.staff.store', $event), [
            'email' => $staff->email,
            'gate' => 'Gate A',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('organizer.events.staff.index', $event))
        ->assertOk();

    $this->actingAs($owner)
        ->post(route('organizer.events.staff.store', $event), [
            'email' => $staff->email,
            'gate' => 'Gate VIP',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('event_staff', [
        'event_id' => $event->getKey(),
        'user_id' => $staff->getKey(),
        'gate' => 'Gate VIP',
    ]);
});

it('shows assigned events and the gate on the scan page', function () {
    [, , $event] = eventStaffContext();

    $staff = User::factory()->staff()->create();
    $event->staff()->attach($staff->getKey(), ['gate' => 'Gate Utama']);

    $this->actingAs($staff)
        ->get(route('scan.index'))
        ->assertOk()
        ->assertSee($event->title)
        ->assertSee('Gate Utama');

    $this->actingAs($staff)
        ->get(route('scan.show', $event))
        ->assertOk()
        ->assertSee($event->title)
        ->assertSee('Gate Utama');
});

it('blocks staff from scanning events they are not assigned to', function () {
    [, , $event] = eventStaffContext();

    $assigned = User::factory()->staff()->create();
    $event->staff()->attach($assigned->getKey(), ['gate' => 'Gate A']);

    $unassigned = User::factory()->staff()->create();

    $this->actingAs($unassigned)
        ->get(route('scan.show', $event))
        ->assertForbidden();

    $otherEvent = Event::factory()->create([
        'title' => 'Event Lain',
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(10),
    ]);

    $this->actingAs($assigned)
        ->get(route('scan.show', $otherEvent))
        ->assertForbidden();
});

it('prevents staff from modifying event or ticket data', function () {
    [, , $event] = eventStaffContext();

    $staff = User::factory()->staff()->create();
    $event->staff()->attach($staff->getKey(), ['gate' => 'Gate A']);

    expect(Gate::forUser($staff)->allows('update', $event))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('manageTickets', $event))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('manageStaff', $event))->toBeFalse();

    $this->actingAs($staff)
        ->get(route('organizer.events.edit', $event))
        ->assertForbidden();

    $this->actingAs($staff)
        ->get(route('organizer.events.tickets.index', $event))
        ->assertForbidden();

    $this->actingAs($staff)
        ->get(route('admin.events.staff.index', $event))
        ->assertForbidden();
});

it('sends staff to the scan index as their home page', function () {
    $staff = User::factory()->staff()->create();

    expect($staff->homeRouteName())->toBe('scan.index');

    $admin = User::factory()->admin()->create();
    $admin->assignRole('staff');

    expect($admin->fresh()->homeRouteName())->toBe('dashboard');
});

it('requires authentication to open the scan page', function () {
    [, , $event] = eventStaffContext();

    $this->get(route('scan.show', $event))->assertRedirect(route('login'));
});
