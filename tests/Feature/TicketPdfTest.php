<?php

use App\Jobs\SendTicketEmail;
use App\Models\Event;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\TicketIssued;
use App\Support\TicketPdf;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @return array{0: Order, 1: Ticket}
 */
function ticketPdfOrder(array $orderOverrides = []): array
{
    $event = Event::factory()->create([
        'title' => 'Konser PDF Nusa',
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(5)->setTime(19, 30),
        'is_online' => false,
    ]);

    $event->venue()->update(['name' => 'Aula Nusa']);

    $ticketType = TicketType::factory()->forEvent($event)->create([
        'name' => 'Reguler PDF',
        'price' => 150000,
    ]);

    $order = Order::factory()->paid()->create(array_merge([
        'event_id' => $event->getKey(),
        'buyer_name' => 'Budi Pemesan',
        'buyer_email' => 'budi@example.com',
        'subtotal' => 150000,
        'total_amount' => 150000,
    ], $orderOverrides));

    $item = $order->items()->create([
        'ticket_type_id' => $ticketType->getKey(),
        'quantity' => 1,
        'unit_price' => 150000,
        'subtotal' => 150000,
    ]);

    $ticket = Ticket::query()->create([
        'ticket_code' => 'TIX-'.Str::upper(Str::random(24)),
        'order_item_id' => $item->getKey(),
        'order_id' => $order->getKey(),
        'event_id' => $event->getKey(),
        'ticket_type_id' => $ticketType->getKey(),
        'holder_name' => 'Sari Pemegang',
        'status' => Ticket::STATUS_ACTIVE,
        'issued_at' => now(),
    ]);

    return [$order->fresh(['event.venue', 'tickets.ticketType']), $ticket];
}

it('renders a pdf template with event, date, venue, ticket, holder, code, and qr', function () {
    [$order, $ticket] = ticketPdfOrder();

    $html = view('tickets.pdf', [
        'order' => $order,
        'tickets' => $order->tickets,
        'siteName' => 'LinyTix',
    ])->render();

    expect($html)
        ->toContain('Konser PDF Nusa')
        ->toContain('Aula Nusa')
        ->toContain('Reguler PDF')
        ->toContain('Sari Pemegang')
        ->toContain($ticket->ticket_code)
        ->toContain($order->order_number)
        ->toContain((string) $order->event->start_at->year)
        ->toContain('data:image/svg+xml;base64,');

    preg_match('/data:image\/svg\+xml;base64,([^\"]+)/', $html, $matches);

    expect($matches)->not->toBeEmpty()
        ->and(base64_decode($matches[1]))->toContain('<svg');
});

it('generates a valid pdf file for an order', function () {
    [$order] = ticketPdfOrder();

    $pdf = TicketPdf::output($order);

    expect(str_starts_with($pdf, '%PDF'))->toBeTrue()
        ->and(strlen($pdf))->toBeGreaterThan(1000);
});

it('allows the owner and admin to download the order pdf', function () {
    $owner = User::factory()->create();
    [$order] = ticketPdfOrder(['user_id' => $owner->getKey()]);

    $this->actingAs($owner)
        ->get(route('akun.orders.pdf', $order))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.orders.pdf', $order))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('blocks other users from downloading the order pdf', function () {
    $owner = User::factory()->create();
    [$order] = ticketPdfOrder(['user_id' => $owner->getKey()]);

    $this->actingAs(User::factory()->create())
        ->get(route('akun.orders.pdf', $order))
        ->assertForbidden();
});

it('redirects guests away from account pdf downloads', function () {
    [$order] = ticketPdfOrder();

    $this->get(route('akun.orders.pdf', $order))->assertRedirect(route('login'));
});

it('allows guest pdf downloads only through a valid signed url', function () {
    [$order] = ticketPdfOrder();

    $this->get(route('guest.orders.pdf', $order))->assertForbidden();

    $url = URL::temporarySignedRoute('guest.orders.pdf', now()->addHour(), ['order' => $order->getKey()]);

    $this->get($url)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('attaches the same generated pdf to the ticket email', function () {
    [$order] = ticketPdfOrder();

    $mail = (new TicketIssued($order, 'https://example.test/tiket'))->toMail(new stdClass);

    expect($mail->rawAttachments)->toHaveCount(1)
        ->and($mail->rawAttachments[0]['name'])->toBe('e-tiket-'.$order->order_number.'.pdf')
        ->and($mail->rawAttachments[0]['options']['mime'])->toBe('application/pdf')
        ->and(str_starts_with($mail->rawAttachments[0]['data'], '%PDF'))->toBeTrue();
});

it('shows pdf links on account, guest, and checkout pages', function () {
    $owner = User::factory()->create();
    [$order] = ticketPdfOrder(['user_id' => $owner->getKey()]);

    $this->actingAs($owner)
        ->get(route('akun.orders.show', $order))
        ->assertOk()
        ->assertSee(route('akun.orders.pdf', $order), false);

    $this->actingAs($owner)
        ->get(route('akun.tickets.show', $order->tickets->first()))
        ->assertOk()
        ->assertSee(route('akun.tickets.pdf', $order->tickets->first()), false);

    $guestOrder = ticketPdfOrder()[0];

    $signedUrl = URL::temporarySignedRoute('guest.orders.show', now()->addHour(), ['order' => $guestOrder->getKey()]);

    $this->get($signedUrl)->assertOk()->assertSee('Unduh PDF');

    $checkoutUrl = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $guestOrder->getKey()]);

    $this->get($checkoutUrl)->assertOk()->assertSee('Unduh PDF e-tiket');
});

it('shows failed ticket emails to admins and allows resending', function () {
    Queue::fake();

    [$order] = ticketPdfOrder();

    NotificationLog::query()->create([
        'order_id' => $order->getKey(),
        'channel' => 'email',
        'recipient' => $order->buyer_email,
        'template' => 'ticket_issued',
        'status' => 'failed',
        'error' => 'SMTP timeout',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.orders'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Failed')
        ->assertSee('SMTP timeout');

    $this->actingAs($admin)
        ->post(route('admin.orders.resend-ticket', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(SendTicketEmail::class, fn (SendTicketEmail $job) => $job->orderId === $order->getKey());
});

it('prevents non admins from opening the admin orders page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.orders'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['is_active' => true])->assignRole(Role::CUSTOMER))
        ->get(route('admin.orders'))
        ->assertForbidden();
});

it('does not resend tickets for an unpaid order', function () {
    Queue::fake();

    [$order] = ticketPdfOrder(['status' => Order::STATUS_PENDING, 'paid_at' => null]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.orders.resend-ticket', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    Queue::assertNotPushed(SendTicketEmail::class);
});
