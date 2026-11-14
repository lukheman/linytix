<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\EventApproved;
use App\Notifications\EventCancelled;
use App\Notifications\EventRejected;
use Illuminate\Support\Facades\Notification;

/**
 * @return array{0: User, 1: Organizer}
 */
function moderationOrganizer(): array
{
    $owner = User::factory()->organizer()->create();

    $organizer = Organizer::factory()->forOwner($owner)->create([
        'name' => 'Nusa Moderasi',
        'slug' => 'nusa-moderasi',
        'contact_email' => 'kontak@nusa-moderasi.test',
        'is_verified' => true,
        'verified_at' => now(),
    ]);

    return [$owner, $organizer];
}

function moderationPaidOrder(Event $event, string $buyerEmail = 'pembeli@example.test'): Order
{
    $order = Order::factory()->paid()->create([
        'event_id' => $event->getKey(),
        'buyer_name' => 'Pembeli Moderasi',
        'buyer_email' => $buyerEmail,
        'subtotal' => 150000,
        'total_amount' => 150000,
    ]);

    Payment::factory()->success()->create([
        'order_id' => $order->getKey(),
        'amount' => 150000,
    ]);

    return $order;
}

it('shows the pending review queue newest first and an event detail page', function () {
    $admin = User::factory()->admin()->create();
    [$owner, $organizer] = moderationOrganizer();

    $older = Event::factory()->forOrganizer($organizer)->pendingReview()->create([
        'title' => 'Event Antrean Lama',
        'created_at' => now()->subDay(),
    ]);

    $newer = Event::factory()->forOrganizer($organizer)->pendingReview()->create([
        'title' => 'Event Antrean Baru',
        'created_at' => now(),
    ]);

    TicketType::factory()->forEvent($newer)->create([
        'name' => 'Tiket Presale',
        'price' => 125000,
        'quota' => 100,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.events', ['status' => 'pending_review']))
        ->assertOk()
        ->assertSeeInOrder(['Event Antrean Baru', 'Event Antrean Lama'])
        ->assertSee(route('admin.events.show', $newer), false);

    $this->actingAs($admin)
        ->get(route('admin.events.show', $newer))
        ->assertOk()
        ->assertSee('Event Antrean Baru')
        ->assertSee('Tiket Presale')
        ->assertSee('Nusa Moderasi')
        ->assertSee($owner->email)
        ->assertSee('Jenis Tiket')
        ->assertSee('Aksi Moderasi')
        ->assertSee(route('admin.events.approve', $newer), false)
        ->assertSee(route('admin.events.reject', $newer), false);

    $this->actingAs($owner)
        ->get(route('admin.events.show', $newer))
        ->assertForbidden();

    expect($older->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW);
});

it('approves an event, fills approval fields, audits it, and emails the organizer', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    [$owner, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->pendingReview()->create([
        'title' => 'Event Siap Tayang',
        'rejection_reason' => 'Catatan lama',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.events.approve', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    $event->refresh();

    expect($event->status)->toBe(Event::STATUS_PUBLISHED)
        ->and($event->published_at)->not->toBeNull()
        ->and($event->approved_by)->toBe($admin->getKey())
        ->and($event->approved_at)->not->toBeNull()
        ->and($event->rejection_reason)->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'event.approved',
        'entity' => 'event',
        'entity_id' => $event->getKey(),
        'user_id' => $admin->getKey(),
    ]);

    Notification::assertSentOnDemand(
        EventApproved::class,
        function (EventApproved $notification, array $channels, $notifiable) use ($owner, $event): bool {
            return $notifiable->routeNotificationFor('mail') === $owner->email
                && $notification->event->is($event);
        },
    );
});

it('rejects an event with a required reason and notifies the organizer', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    [$owner, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->pendingReview()->create([
        'title' => 'Event Perlu Revisi',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.events.show', $event))
        ->post(route('admin.events.reject', $event), ['rejection_reason' => ''])
        ->assertSessionHasErrors('rejection_reason');

    expect($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW);

    Notification::assertNothingSent();

    $reason = 'Poster belum mencantumkan nama organizer.';

    $this->actingAs($admin)
        ->post(route('admin.events.reject', $event), [
            'rejection_reason' => $reason,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $event->refresh();

    expect($event->status)->toBe(Event::STATUS_REJECTED)
        ->and($event->published_at)->toBeNull()
        ->and($event->approved_by)->toBeNull()
        ->and($event->approved_at)->toBeNull()
        ->and($event->rejection_reason)->toBe($reason);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'event.rejected',
        'entity_id' => $event->getKey(),
        'user_id' => $admin->getKey(),
    ]);

    $this->actingAs($owner)
        ->get(route('organizer.events.index'))
        ->assertOk()
        ->assertSee($reason);

    Notification::assertSentOnDemand(
        EventRejected::class,
        function (EventRejected $notification, array $channels, $notifiable) use ($owner, $event, $reason): bool {
            return $notifiable->routeNotificationFor('mail') === $owner->email
                && $notification->event->is($event)
                && $notification->reason === $reason;
        },
    );
});

it('cancels a published event, refunds every paid order, and notifies the organizer', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    [$owner, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->create([
        'title' => 'Event Dibatalkan Admin',
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(10),
    ]);

    $paid = moderationPaidOrder($event);
    $pending = Order::factory()->create([
        'event_id' => $event->getKey(),
        'status' => Order::STATUS_PENDING,
        'paid_at' => null,
    ]);

    $reason = 'Venue tidak dapat digunakan karena force majeure.';

    $this->actingAs($admin)
        ->get(route('admin.events.show', $event))
        ->assertOk()
        ->assertSee('Batalkan Event')
        ->assertSee(route('admin.events.cancel'), false)
        ->assertSee('1 pesanan lunas');

    $this->actingAs($admin)
        ->post(route('admin.events.cancel'), [
            'event_id' => $event->getKey(),
            'reason' => $reason,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->status)->toBe(Event::STATUS_CANCELLED)
        ->and($paid->fresh()->status)->toBe(Order::STATUS_REFUNDED)
        ->and($pending->fresh()->status)->toBe(Order::STATUS_PENDING)
        ->and(Refund::query()->where('order_id', $paid->getKey())->exists())->toBeTrue()
        ->and(Refund::query()->where('order_id', $pending->getKey())->exists())->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'event.cancelled',
        'entity_id' => $event->getKey(),
        'user_id' => $admin->getKey(),
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'refund.batch_requested',
        'entity_id' => $event->getKey(),
        'user_id' => $admin->getKey(),
    ]);

    Notification::assertSentOnDemand(
        EventCancelled::class,
        function (EventCancelled $notification, array $channels, $notifiable) use ($owner, $event, $reason): bool {
            return $notifiable->routeNotificationFor('mail') === $owner->email
                && $notification->event->is($event)
                && $notification->reason === $reason;
        },
    );
});

it('only cancels events with published or sold out status', function (string $status) {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    [, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->create([
        'status' => $status,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.refunds'))
        ->post(route('admin.events.cancel'), [
            'event_id' => $event->getKey(),
            'reason' => 'Pembatalan tidak diizinkan untuk status ini.',
        ])
        ->assertSessionHasErrors('event_id');

    expect($event->fresh()->status)->toBe($status);
    expect(AuditLog::query()->where('action', 'event.cancelled')->count())->toBe(0);
    Notification::assertNothingSent();
})->with([
    'ended' => [Event::STATUS_ENDED],
    'rejected' => [Event::STATUS_REJECTED],
    'draft' => [Event::STATUS_DRAFT],
]);

it('allows cancel action for sold out events', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    [, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->soldOut()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.cancel'), [
            'event_id' => $event->getKey(),
            'reason' => 'Event sold out dibatalkan karena kendala teknis.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->status)->toBe(Event::STATUS_CANCELLED);
});

it('restricts event moderation and cancellation to admins', function () {
    [$owner, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->pendingReview()->create();
    $published = Event::factory()->forOrganizer($organizer)->create([
        'status' => Event::STATUS_PUBLISHED,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.events.show', $event))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('admin.events.approve', $event))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('admin.events.reject', $event), ['rejection_reason' => 'Tidak berwenang'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('admin.events.cancel'), [
            'event_id' => $published->getKey(),
            'reason' => 'Tidak berwenang membatalkan event.',
        ])
        ->assertForbidden();

    expect($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW)
        ->and($published->fresh()->status)->toBe(Event::STATUS_PUBLISHED);
});

it('allows admins to adjust ticket sales settings from the event detail page', function () {
    $admin = User::factory()->admin()->create();
    [, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->create();
    $ticket = TicketType::factory()->forEvent($event)->create([
        'quota' => 100,
        'sold_count' => 10,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.events.tickets.sales', [$event, $ticket]), [
            'quota' => 120,
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($ticket->fresh()->quota)->toBe(120)
        ->and(AuditLog::query()->where('action', 'ticket_type.sales_updated')->count())->toBe(1);
});

it('rejects invalid ticket sales settings from the admin event page', function () {
    $admin = User::factory()->admin()->create();
    [, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->create();
    $ticket = TicketType::factory()->forEvent($event)->create([
        'quota' => 100,
        'sold_count' => 10,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.events.tickets.sales', [$event, $ticket]), [
            'quota' => 5,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('quota');

    expect($ticket->fresh()->quota)->toBe(100);
});

it('shows ticket sales settings on the admin event detail page', function () {
    $admin = User::factory()->admin()->create();
    [, $organizer] = moderationOrganizer();

    $event = Event::factory()->forOrganizer($organizer)->create();
    TicketType::factory()->forEvent($event)->create(['quota' => 100, 'is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.events.show', $event))
        ->assertOk()
        ->assertSee('Pengaturan Penjualan Tiket')
        ->assertSee('Simpan Penjualan');
});
