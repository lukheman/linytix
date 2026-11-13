<?php

use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;

/**
 * @return array{0: Event, 1: User, 2: Ticket, 3: TicketType, 4: Order}
 */
function ticketScanContext(array $eventOverrides = []): array
{
    $owner = User::factory()->organizer()->create();

    $organizer = Organizer::factory()
        ->forOwner($owner)
        ->create([
            'name' => 'Nusa Scan',
            'slug' => 'nusa-scan',
            'is_verified' => true,
            'verified_at' => now(),
        ]);

    $event = Event::factory()
        ->forOrganizer($organizer)
        ->create(array_merge([
            'title' => 'Konser Scan Nusa',
            'status' => Event::STATUS_PUBLISHED,
            'published_at' => now(),
            'timezone' => 'Asia/Jakarta',
            'start_at' => now()->addDays(2),
        ], $eventOverrides));

    $ticketType = TicketType::factory()->forEvent($event)->create([
        'name' => 'Reguler',
        'price' => 150000,
        'quota' => 100,
        'sold_count' => 1,
    ]);

    $order = Order::factory()->paid()->create([
        'event_id' => $event->getKey(),
        'subtotal' => 150000,
        'total_amount' => 150000,
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);

    $ticket = Ticket::factory()->create([
        'ticket_code' => 'TIX-SCAN-0001',
        'order_id' => $order->getKey(),
        'order_item_id' => $item->getKey(),
        'event_id' => $event->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'holder_name' => 'Budi Santoso',
        'status' => Ticket::STATUS_ACTIVE,
    ]);

    $staff = User::factory()->staff()->create();
    $event->staff()->attach($staff->getKey(), ['gate' => 'Gate A']);

    return [$event, $staff, $ticket, $ticketType, $order];
}

it('validates an active paid ticket and records the check-in', function () {
    [$event, $staff, $ticket] = ticketScanContext();

    $this->actingAs($staff)
        ->get(route('scan.show', $event))
        ->assertOk()
        ->assertSee('Input Manual')
        ->assertSee('Html5Qrcode.getCameras', false)
        ->assertSee('id="reader"', false)
        ->assertSee('name="ticket_code"', false);

    $startedAt = microtime(true);

    $response = $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ]);

    $durationMs = (microtime(true) - $startedAt) * 1000;

    $response->assertOk()
        ->assertJson([
            'result' => 'success',
            'title' => 'Tiket Valid',
            'gate' => 'Gate A',
            'ticket' => [
                'ticket_code' => 'TIX-SCAN-0001',
                'holder_name' => 'Budi Santoso',
                'ticket_type' => 'Reguler',
            ],
        ]);

    expect($durationMs)->toBeLessThan(3000);

    $ticket->refresh();

    expect($ticket->status)->toBe(Ticket::STATUS_USED)
        ->and($ticket->used_at)->not->toBeNull();

    $this->assertDatabaseHas('check_ins', [
        'ticket_id' => $ticket->getKey(),
        'event_id' => $event->getKey(),
        'scanned_by' => $staff->getKey(),
        'gate' => 'Gate A',
        'result' => 'success',
    ]);
});

it('returns duplicate on a second scan without changing the ticket again', function () {
    [$event, $staff, $ticket] = ticketScanContext();

    $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertOk()->assertJson(['result' => 'success']);

    $firstUsedAt = $ticket->fresh()->used_at;

    $response = $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ]);

    $response->assertOk()->assertJson([
        'result' => 'duplicate',
        'title' => 'Tiket Sudah Digunakan',
        'reason' => 'already_used',
        'gate' => 'Gate A',
    ]);

    expect($response->json('previous.gate'))->toBe('Gate A')
        ->and($ticket->fresh()->status)->toBe(Ticket::STATUS_USED)
        ->and($ticket->fresh()->used_at->equalTo($firstUsedAt))->toBeTrue();

    expect(CheckIn::query()->where('ticket_id', $ticket->getKey())->where('result', 'success')->count())->toBe(1)
        ->and(CheckIn::query()->where('ticket_id', $ticket->getKey())->where('result', 'duplicate')->count())->toBe(1);
});

it('treats two rapid scans as one success and one duplicate', function () {
    [$event, $staff, $ticket] = ticketScanContext();

    $first = $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ]);

    $second = $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ]);

    expect($first->json('result'))->toBe('success')
        ->and($second->json('result'))->toBe('duplicate');

    expect(CheckIn::query()->where('ticket_id', $ticket->getKey())->where('result', 'success')->count())->toBe(1)
        ->and(CheckIn::query()->where('ticket_id', $ticket->getKey())->where('result', 'duplicate')->count())->toBe(1);
});

it('returns invalid for an unknown ticket code and records the attempt', function () {
    [$event, $staff] = ticketScanContext();

    $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => 'TIX-TIDAK-ADA',
    ])->assertOk()->assertJson([
        'result' => 'invalid',
        'reason' => 'not_found',
        'message' => 'Kode tiket tidak ditemukan.',
        'ticket' => null,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'ticket.scan.invalid',
        'entity' => 'event',
        'entity_id' => $event->getKey(),
        'user_id' => $staff->getKey(),
    ]);
});

it('rejects a ticket from another event', function () {
    [$eventA, $staff, $ticket] = ticketScanContext();

    $eventB = Event::factory()->create([
        'title' => 'Event Scan Lain',
        'status' => Event::STATUS_PUBLISHED,
        'published_at' => now(),
        'start_at' => now()->addDays(3),
    ]);

    $eventB->staff()->attach($staff->getKey(), ['gate' => 'Gate B']);

    $this->actingAs($staff)->postJson(route('scan.store', $eventB), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertOk()->assertJson([
        'result' => 'invalid',
        'reason' => 'wrong_event',
    ]);

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_ACTIVE);

    $this->assertDatabaseHas('check_ins', [
        'ticket_id' => $ticket->getKey(),
        'event_id' => $eventB->getKey(),
        'result' => 'invalid',
        'gate' => 'Gate B',
    ]);
});

it('rejects unpaid, refunded, and cancelled tickets', function () {
    [$event, $staff, $ticket, , $order] = ticketScanContext();

    $order->forceFill(['status' => Order::STATUS_PENDING])->save();

    $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertOk()->assertJson([
        'result' => 'invalid',
        'reason' => 'order_not_paid',
    ]);

    $order->forceFill(['status' => Order::STATUS_PAID])->save();
    $ticket->forceFill(['status' => Ticket::STATUS_REFUNDED])->save();

    $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertOk()->assertJson([
        'result' => 'invalid',
        'reason' => 'ticket_inactive',
    ]);

    $ticket->forceFill(['status' => Ticket::STATUS_CANCELLED])->save();

    $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertOk()->assertJson([
        'result' => 'invalid',
        'reason' => 'ticket_inactive',
    ]);
});

it('rejects scans when the event is cancelled', function () {
    [$event, $staff, $ticket] = ticketScanContext();

    $event->forceFill(['status' => Event::STATUS_CANCELLED])->save();

    $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertOk()->assertJson([
        'result' => 'invalid',
        'reason' => 'event_cancelled',
    ]);

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_ACTIVE);
});

it('blocks unassigned staff from submitting a scan', function () {
    [$event, , $ticket] = ticketScanContext();

    $other = User::factory()->staff()->create();

    $this->actingAs($other)->postJson(route('scan.store', $event), [
        'ticket_code' => $ticket->ticket_code,
    ])->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_ACTIVE);
});

it('rate limits scans to sixty requests per minute', function () {
    [$event, $staff] = ticketScanContext();

    for ($attempt = 1; $attempt <= 60; $attempt++) {
        $this->actingAs($staff)->postJson(route('scan.store', $event), [
            'ticket_code' => 'TIX-RATE-'.$attempt,
        ])->assertOk()->assertJson(['result' => 'invalid']);
    }

    $this->actingAs($staff)->postJson(route('scan.store', $event), [
        'ticket_code' => 'TIX-RATE-61',
    ])->assertStatus(429);
});
