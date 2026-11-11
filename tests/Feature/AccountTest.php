<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Build a consistent paid order with one item and one active ticket.
 *
 * @return array{0: Order, 1: Ticket}
 */
function makeAccountPaidOrder(User $user, array $orderAttributes = []): array
{
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->forEvent($event)->create();

    $order = Order::factory()->paid()->create(array_merge([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ], $orderAttributes));

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'ticket_type_id' => $ticketType->id,
        'quantity' => 1,
        'unit_price' => 100000,
        'subtotal' => 100000,
    ]);

    $ticket = Ticket::factory()->create([
        'order_id' => $order->id,
        'order_item_id' => $item->id,
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'ticket_code' => 'TIX-'.Str::upper(Str::random(12)),
    ]);

    return [$order, $ticket];
}

it('requires authentication for account pages', function () {
    $this->get('/akun')->assertRedirect('/login');
    $this->get('/akun/pesanan')->assertRedirect('/login');
    $this->get('/akun/tiket')->assertRedirect('/login');
});

it('shows the account dashboard to logged-in customers, verified or not', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/akun')
        ->assertOk()
        ->assertSee('Halo, '.$user->name.'.')
        ->assertSee('Email belum terverifikasi');
});

it('blocks inactive users from the account area', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)->get('/akun')->assertForbidden();
});

it('lists only the authenticated user orders', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Order::factory()->paid()->create([
        'user_id' => $user->id,
        'order_number' => 'ORD-OWN-100',
    ]);

    Order::factory()->paid()->create([
        'user_id' => $other->id,
        'order_number' => 'ORD-OTHER-200',
    ]);

    $this->actingAs($user)
        ->get('/akun/pesanan')
        ->assertOk()
        ->assertSee('ORD-OWN-100')
        ->assertDontSee('ORD-OTHER-200');
});

it('shows order details to the owner and forbids other users', function () {
    $user = User::factory()->create();
    [$order] = makeAccountPaidOrder($user, ['order_number' => 'ORD-DETAIL-1']);

    $this->actingAs($user)
        ->get(route('akun.orders.show', $order))
        ->assertOk()
        ->assertSee('ORD-DETAIL-1')
        ->assertSee($order->event->title);

    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('akun.orders.show', $order))
        ->assertForbidden();
});

it('lists only the authenticated user tickets', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    [, $ownTicket] = makeAccountPaidOrder($user);
    [, $otherTicket] = makeAccountPaidOrder($other);

    $this->actingAs($user)
        ->get('/akun/tiket')
        ->assertOk()
        ->assertSee($ownTicket->ticket_code)
        ->assertDontSee($otherTicket->ticket_code);
});

it('renders a QR code for the ticket owner and forbids other users', function () {
    $user = User::factory()->create();
    [, $ticket] = makeAccountPaidOrder($user);

    $this->actingAs($user)
        ->get(route('akun.tickets.show', $ticket))
        ->assertOk()
        ->assertSee($ticket->ticket_code)
        ->assertSee('<svg', false);

    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('akun.tickets.show', $ticket))
        ->assertForbidden();
});
