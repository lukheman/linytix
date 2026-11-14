<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{0: User, 1: Organizer}
 */
function eventOrganizerOwner(array $organizerOverrides = []): array
{
    $user = User::factory()->organizer()->create();

    $organizer = Organizer::factory()
        ->forOwner($user)
        ->create(array_merge([
            'name' => 'Nusa Live',
            'slug' => 'nusa-live',
            'is_verified' => true,
            'verified_at' => now(),
        ], $organizerOverrides));

    return [$user, $organizer];
}

function eventPayload(array $overrides = []): array
{
    $category = Category::factory()->create();
    $venue = Venue::factory()->create();

    return array_merge([
        'title' => 'Konser Nusa 2026',
        'category_id' => $category->getKey(),
        'is_online' => '0',
        'venue_id' => $venue->getKey(),
        'timezone' => 'Asia/Jakarta',
        'start_at' => '2026-12-01T19:00',
        'end_at' => '2026-12-01T22:00',
        'doors_open_at' => '2026-12-01T18:00',
        'short_desc' => 'Konser tahunan Nusa Live.',
        'description' => 'Deskripsi lengkap event konser Nusa Live.',
        'terms' => 'Tiket tidak dapat dikembalikan kecuali event dibatalkan.',
        'poster' => UploadedFile::fake()->image('poster.jpg', 1200, 800),
    ], $overrides);
}

it('creates a draft event with required fields and poster upload', function () {
    Storage::fake('public');
    [$user, $organizer] = eventOrganizerOwner();

    $this->actingAs($user)
        ->post(route('organizer.events.store'), eventPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $event = Event::query()->firstOrFail();

    expect($event->organizer_id)->toBe($organizer->getKey())
        ->and($event->status)->toBe(Event::STATUS_DRAFT)
        ->and($event->title)->toBe('Konser Nusa 2026')
        ->and($event->slug)->toBe('konser-nusa-2026')
        ->and($event->poster_url)->toContain('/storage/events/')
        ->and($event->published_at)->toBeNull();

    expect(AuditLog::query()->where('action', 'event.created')->count())->toBe(1);
});

it('requires a poster when creating an event', function () {
    Storage::fake('public');
    [$user] = eventOrganizerOwner();

    $payload = eventPayload();
    unset($payload['poster']);

    $this->actingAs($user)
        ->post(route('organizer.events.store'), $payload)
        ->assertSessionHasErrors('poster');

    expect(Event::query()->count())->toBe(0);
});

it('rejects an invalid poster mime type', function () {
    Storage::fake('public');
    [$user] = eventOrganizerOwner();

    $this->actingAs($user)
        ->post(route('organizer.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->create('poster.pdf', 100, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('poster');

    expect(Event::query()->count())->toBe(0);
});

it('rejects a poster with dimensions below the minimum', function () {
    Storage::fake('public');
    [$user] = eventOrganizerOwner();

    $this->actingAs($user)
        ->post(route('organizer.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->image('poster.jpg', 120, 90),
        ]))
        ->assertSessionHasErrors('poster');

    expect(Event::query()->count())->toBe(0);
});

it('blocks submit when there is no active ticket', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->draft()
        ->create([
            'title' => 'Event Tanpa Tiket',
            'poster_url' => 'https://cdn.example.test/poster.jpg',
        ]);

    $this->actingAs($user)
        ->post(route('organizer.events.submit', $event))
        ->assertSessionHasErrors('tickets');

    expect($event->fresh()->status)->toBe(Event::STATUS_DRAFT);
});

it('submits a complete draft for review', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->draft()
        ->create([
            'title' => 'Event Siap Review',
            'poster_url' => 'https://cdn.example.test/poster.jpg',
        ]);

    TicketType::factory()->forEvent($event)->create([
        'is_active' => true,
        'quota' => 10,
        'sold_count' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('organizer.events.submit', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW)
        ->and(AuditLog::query()->where('action', 'event.submitted')->count())->toBe(1);
});

it('locks critical fields after the event is submitted for review', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->pendingReview()
        ->create(['title' => 'Judul Terkunci']);

    $this->actingAs($user)
        ->put(route('organizer.events.update', $event), eventPayload(['title' => 'Judul Baru']))
        ->assertForbidden();

    expect($event->fresh()->title)->toBe('Judul Terkunci')
        ->and($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW);
});

it('allows only description and gallery edits on a published event', function () {
    Storage::fake('public');
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->create([
            'title' => 'Judul Published',
            'description' => 'Deskripsi lama.',
            'status' => Event::STATUS_PUBLISHED,
        ]);

    $this->actingAs($user)
        ->put(route('organizer.events.update', $event), [
            'description' => 'Deskripsi baru.',
            'title' => 'Judul Diubah',
            'gallery' => [UploadedFile::fake()->image('galeri.jpg', 800, 600)],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $event->refresh();

    expect($event->description)->toBe('Deskripsi baru.')
        ->and($event->title)->toBe('Judul Published')
        ->and($event->images()->count())->toBe(1);
});

it('removes gallery images from a draft event', function () {
    Storage::fake('public');
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create([
        'poster_url' => 'https://cdn.example.test/poster.jpg',
    ]);

    $image = EventImage::factory()->create(['event_id' => $event->getKey()]);

    $this->actingAs($user)
        ->put(route('organizer.events.update', $event), eventPayload([
            'remove_images' => [$image->getKey()],
        ]))
        ->assertRedirect();

    expect($event->images()->count())->toBe(0);
});

it('allows deleting a draft but not a published event', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $draft = Event::factory()->forOrganizer($organizer)->draft()->create();
    $published = Event::factory()->forOrganizer($organizer)->create(['status' => Event::STATUS_PUBLISHED]);

    $this->actingAs($user)
        ->delete(route('organizer.events.destroy', $published))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('organizer.events.destroy', $draft))
        ->assertRedirect(route('organizer.events.index'));

    expect(Event::query()->whereKey($draft->getKey())->exists())->toBeFalse()
        ->and(Event::query()->whereKey($published->getKey())->exists())->toBeTrue();
});

it('prevents other organizers and customers from managing an event', function () {
    [, $organizer] = eventOrganizerOwner();
    $event = Event::factory()->forOrganizer($organizer)->draft()->create();

    $otherOrganizer = User::factory()->organizer()->create();
    Organizer::factory()->forOwner($otherOrganizer)->create();

    $this->actingAs($otherOrganizer)
        ->get(route('organizer.events.edit', $event))
        ->assertForbidden();

    $this->actingAs($otherOrganizer)
        ->get(route('organizer.events.tickets.index', $event))
        ->assertForbidden();

    $this->actingAs($otherOrganizer)
        ->delete(route('organizer.events.destroy', $event))
        ->assertForbidden();

    $this->actingAs(User::factory()->customer()->create())
        ->get(route('organizer.events.edit', $event))
        ->assertForbidden();
});

it('rejects invalid gallery and lineup uploads', function () {
    Storage::fake('public');
    [$user] = eventOrganizerOwner();

    $this->actingAs($user)
        ->post(route('organizer.events.store'), eventPayload([
            'gallery' => [UploadedFile::fake()->create('galeri.pdf', 100, 'application/pdf')],
            'lineups' => [
                [
                    'name' => 'Pembicara',
                    'photo' => UploadedFile::fake()->image('lineup.jpg', 120, 120),
                ],
            ],
        ]))
        ->assertSessionHasErrors(['gallery.0', 'lineups.0.photo']);

    expect(Event::query()->count())->toBe(0);
});

it('requires an online url for an online event', function () {
    Storage::fake('public');
    [$user] = eventOrganizerOwner();

    $payload = eventPayload([
        'is_online' => '1',
        'venue_id' => null,
    ]);
    unset($payload['online_url']);

    $this->actingAs($user)
        ->post(route('organizer.events.store'), $payload)
        ->assertSessionHasErrors('online_url');

    expect(Event::query()->count())->toBe(0);
});

it('lets a rejected event be edited and resubmitted', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->create([
            'status' => Event::STATUS_REJECTED,
            'published_at' => null,
            'poster_url' => 'https://cdn.example.test/poster.jpg',
            'rejection_reason' => 'Poster kurang jelas.',
        ]);

    TicketType::factory()->forEvent($event)->create([
        'is_active' => true,
        'quota' => 10,
        'sold_count' => 0,
    ]);

    $this->actingAs($user)
        ->put(route('organizer.events.update', $event), eventPayload([
            'title' => 'Judul Setelah Revisi',
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->title)->toBe('Judul Setelah Revisi');

    $this->actingAs($user)
        ->post(route('organizer.events.submit', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW)
        ->and($event->fresh()->rejection_reason)->toBeNull();
});

it('lets an admin approve a pending event', function () {
    [, $organizer] = eventOrganizerOwner();
    $admin = User::factory()->admin()->create();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->pendingReview()
        ->create(['title' => 'Event Menunggu']);

    $this->actingAs($admin)
        ->get(route('admin.events'))
        ->assertOk()
        ->assertSee('Event Menunggu');

    $this->actingAs($admin)
        ->post(route('admin.events.approve', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    $event->refresh();

    expect($event->status)->toBe(Event::STATUS_PUBLISHED)
        ->and($event->published_at)->not->toBeNull()
        ->and($event->approved_by)->toBe($admin->getKey())
        ->and(AuditLog::query()->where('action', 'event.approved')->count())->toBe(1);
});

it('lets an admin reject a pending event with a reason', function () {
    [$user, $organizer] = eventOrganizerOwner();
    $admin = User::factory()->admin()->create();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->pendingReview()
        ->create(['title' => 'Event Ditolak']);

    $this->actingAs($admin)
        ->post(route('admin.events.reject', $event), [])
        ->assertSessionHasErrors('rejection_reason');

    $this->actingAs($admin)
        ->post(route('admin.events.reject', $event), [
            'rejection_reason' => 'Poster kurang jelas.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $event->refresh();

    expect($event->status)->toBe(Event::STATUS_REJECTED)
        ->and($event->rejection_reason)->toBe('Poster kurang jelas.')
        ->and($event->published_at)->toBeNull();

    $this->actingAs($user)
        ->get(route('organizer.events.index'))
        ->assertOk()
        ->assertSee('Poster kurang jelas.');
});

it('prevents non admins from moderating events', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->pendingReview()->create();

    $this->actingAs($user)
        ->post(route('admin.events.approve', $event))
        ->assertForbidden();

    expect($event->fresh()->status)->toBe(Event::STATUS_PENDING_REVIEW);
});

it('manages ticket types and keeps the event minimum price in sync', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create();

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), [
            'name' => 'Regular',
            'price' => 150000,
            'quota' => 100,
            'min_per_order' => 1,
            'max_per_order' => 4,
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($event->fresh()->min_price)->toBe(150000)
        ->and($event->ticketTypes()->count())->toBe(1);

    $ticket = $event->ticketTypes()->firstOrFail();

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.update', [$event, $ticket]), [
            'name' => 'Regular Update',
            'price' => 175000,
            'quota' => 120,
            'min_per_order' => 1,
            'max_per_order' => 4,
            'is_active' => '1',
        ])
        ->assertRedirect();

    expect($ticket->fresh()->price)->toBe(175000)
        ->and($event->fresh()->min_price)->toBe(175000);
});

it('locks ticket changes after the event is submitted', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->pendingReview()
        ->create();

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), [
            'name' => 'Tiket Baru',
            'price' => 100000,
            'quota' => 50,
            'min_per_order' => 1,
            'max_per_order' => 2,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('status');

    expect($event->ticketTypes()->count())->toBe(0);
});

function ticketTypePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Regular',
        'description' => 'Tiket masuk reguler.',
        'price' => 100000,
        'quota' => 50,
        'min_per_order' => 1,
        'max_per_order' => 4,
        'is_active' => '1',
    ], $overrides);
}

it('rejects negative ticket price and quota', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create();

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), ticketTypePayload([
            'price' => -1000,
            'quota' => -5,
        ]))
        ->assertSessionHasErrors(['price', 'quota']);

    expect($event->ticketTypes()->count())->toBe(0);
});

it('rejects a quota below the number of sold tickets', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create();
    $ticket = TicketType::factory()->forEvent($event)->create([
        'quota' => 20,
        'sold_count' => 8,
    ]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.update', [$event, $ticket]), ticketTypePayload([
            'quota' => 5,
        ]))
        ->assertSessionHasErrors('quota');

    expect($ticket->fresh()->quota)->toBe(20);
});

it('blocks ticket quotas that exceed the venue capacity', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $venue = Venue::factory()->create(['capacity' => 100]);
    $event = Event::factory()->forOrganizer($organizer)->draft()->create([
        'venue_id' => $venue->getKey(),
    ]);

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), ticketTypePayload(['quota' => 60]))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), ticketTypePayload([
            'name' => 'VIP',
            'quota' => 50,
        ]))
        ->assertSessionHasErrors('quota');

    expect($event->ticketTypes()->count())->toBe(1);

    $ticket = $event->ticketTypes()->firstOrFail();

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.update', [$event, $ticket]), ticketTypePayload(['quota' => 120]))
        ->assertSessionHasErrors('quota');

    expect($ticket->fresh()->quota)->toBe(60);
});

it('blocks submit when total ticket quota exceeds the venue capacity', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $venue = Venue::factory()->create(['capacity' => 100]);
    $event = Event::factory()->forOrganizer($organizer)->draft()->create([
        'venue_id' => $venue->getKey(),
        'poster_url' => 'https://cdn.example.test/poster.jpg',
    ]);

    TicketType::factory()->forEvent($event)->create(['quota' => 60, 'is_active' => true]);
    TicketType::factory()->forEvent($event)->create(['quota' => 60, 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('organizer.events.submit', $event))
        ->assertSessionHasErrors('tickets');

    expect($event->fresh()->status)->toBe(Event::STATUS_DRAFT);
});

it('prevents deleting a ticket type that already has sales', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create();
    $ticket = TicketType::factory()->forEvent($event)->create([
        'quota' => 20,
        'sold_count' => 2,
    ]);

    $this->actingAs($user)
        ->delete(route('organizer.events.tickets.destroy', [$event, $ticket]))
        ->assertSessionHasErrors('ticket');

    expect(TicketType::query()->whereKey($ticket->getKey())->exists())->toBeTrue();
});

it('keeps the order item price snapshot after a ticket price change', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create();
    $ticket = TicketType::factory()->forEvent($event)->create([
        'price' => 100000,
        'is_active' => true,
    ]);

    $order = Order::factory()->paid()->create(['event_id' => $event->getKey()]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'ticket_type_id' => $ticket->getKey(),
        'quantity' => 2,
        'unit_price' => 100000,
        'subtotal' => 200000,
    ]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.update', [$event, $ticket]), ticketTypePayload([
            'price' => 150000,
        ]))
        ->assertSessionHasNoErrors();

    $orderItem->refresh();

    expect($ticket->fresh()->price)->toBe(150000)
        ->and($orderItem->unit_price)->toBe(100000)
        ->and($orderItem->subtotal)->toBe(200000)
        ->and(AuditLog::query()->where('action', 'ticket_type.updated')->count())->toBe(1);
});

it('validates the ticket sale window against the event schedule', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->draft()->create([
        'timezone' => 'Asia/Jakarta',
        'start_at' => Carbon::parse('2026-12-01 19:00', 'Asia/Jakarta')->utc(),
        'end_at' => Carbon::parse('2026-12-01 22:00', 'Asia/Jakarta')->utc(),
    ]);

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), ticketTypePayload([
            'sale_start_at' => '2026-12-01T10:00',
            'sale_end_at' => '2026-12-01T20:00',
        ]))
        ->assertSessionHasErrors('sale_end_at');

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), ticketTypePayload([
            'sale_start_at' => '2026-12-01T18:00',
            'sale_end_at' => '2026-12-01T18:00',
        ]))
        ->assertSessionHasErrors('sale_end_at');

    $this->actingAs($user)
        ->post(route('organizer.events.tickets.store', $event), ticketTypePayload([
            'sale_start_at' => '2026-11-01T10:00',
            'sale_end_at' => '2026-11-30T22:00',
        ]))
        ->assertSessionHasNoErrors();

    expect($event->ticketTypes()->count())->toBe(1);
});

it('renders the ticket management page with venue capacity information', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $venue = Venue::factory()->create(['capacity' => 100]);
    $event = Event::factory()->forOrganizer($organizer)->draft()->create([
        'venue_id' => $venue->getKey(),
    ]);
    TicketType::factory()->forEvent($event)->create(['quota' => 60, 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('organizer.events.tickets.index', $event))
        ->assertOk()
        ->assertSee('Kapasitas')
        ->assertSee('Total kuota tiket');
});

it('allows organizers to adjust quota and sale window after submission', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->pendingReview()->create([
        'timezone' => 'Asia/Jakarta',
        'start_at' => Carbon::parse('2026-12-01 19:00', 'Asia/Jakarta')->utc(),
    ]);
    $ticket = TicketType::factory()->forEvent($event)->create([
        'quota' => 20,
        'sold_count' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.sales', [$event, $ticket]), [
            'quota' => 30,
            'sale_start_at' => '2026-11-20T10:00',
            'sale_end_at' => '2026-11-30T22:00',
            'is_active' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ticket->refresh();

    expect($ticket->quota)->toBe(30)
        ->and($ticket->sale_start_at?->utc()->toDateTimeString())->toBe('2026-11-20 03:00:00')
        ->and($ticket->sale_end_at?->utc()->toDateTimeString())->toBe('2026-11-30 15:00:00')
        ->and(AuditLog::query()->where('action', 'ticket_type.sales_updated')->count())->toBe(1);
});

it('allows organizers to adjust sales settings after publication', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->create();
    $ticket = TicketType::factory()->forEvent($event)->create([
        'quota' => 50,
        'sold_count' => 10,
    ]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.sales', [$event, $ticket]), [
            'quota' => 60,
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh()->quota)->toBe(60);
});

it('still blocks ticket price changes after the event is submitted', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->pendingReview()->create();
    $ticket = TicketType::factory()->forEvent($event)->create(['price' => 100000]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.update', [$event, $ticket]), ticketTypePayload([
            'price' => 200000,
        ]))
        ->assertSessionHasErrors('status');

    expect($ticket->fresh()->price)->toBe(100000);
});

it('rejects a quota below sold tickets when adjusting sales settings', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->create();
    $ticket = TicketType::factory()->forEvent($event)->create([
        'quota' => 20,
        'sold_count' => 8,
    ]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.sales', [$event, $ticket]), [
            'quota' => 5,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('quota');

    expect($ticket->fresh()->quota)->toBe(20);
});

it('blocks sales settings that exceed the venue capacity after publication', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $venue = Venue::factory()->create(['capacity' => 100]);
    $event = Event::factory()->forOrganizer($organizer)->create([
        'venue_id' => $venue->getKey(),
        'is_online' => false,
    ]);

    TicketType::factory()->forEvent($event)->create(['quota' => 60, 'is_active' => true]);
    $ticket = TicketType::factory()->forEvent($event)->create(['quota' => 20, 'is_active' => true]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.sales', [$event, $ticket]), [
            'quota' => 60,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('quota');

    expect($ticket->fresh()->quota)->toBe(20);
});

it('rejects a sale window ending after the event starts when adjusting sales settings', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->create([
        'timezone' => 'Asia/Jakarta',
        'start_at' => Carbon::parse('2026-12-01 19:00', 'Asia/Jakarta')->utc(),
    ]);
    $ticket = TicketType::factory()->forEvent($event)->create(['quota' => 20]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.sales', [$event, $ticket]), [
            'quota' => 20,
            'sale_end_at' => '2026-12-01T20:00',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('sale_end_at');
});

it('blocks sales settings changes for a cancelled event', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->create([
        'status' => Event::STATUS_CANCELLED,
    ]);
    $ticket = TicketType::factory()->forEvent($event)->create(['quota' => 20]);

    $this->actingAs($user)
        ->put(route('organizer.events.tickets.sales', [$event, $ticket]), [
            'quota' => 30,
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('status');

    expect($ticket->fresh()->quota)->toBe(20);
});

it('shows the sales settings form on the organizer ticket page after publication', function () {
    [$user, $organizer] = eventOrganizerOwner();

    $event = Event::factory()->forOrganizer($organizer)->create();
    TicketType::factory()->forEvent($event)->create(['quota' => 50, 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('organizer.events.tickets.index', $event))
        ->assertOk()
        ->assertSee('Simpan Penjualan')
        ->assertSee('kuota dan jadwal jual masih dapat diubah');
});
