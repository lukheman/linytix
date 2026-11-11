<?php

use App\Models\Order;
use App\Notifications\GuestOrderLink;
use App\Support\GuestOrderAccess;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function makeGuestOrder(array $attributes = []): Order
{
    return Order::factory()->paid()->create(array_merge([
        'user_id' => null,
        'buyer_name' => 'Guest Buyer',
        'buyer_email' => 'guest@example.com',
    ], $attributes));
}

it('shows the guest order lookup form', function () {
    $this->get('/pesanan/cari')
        ->assertOk()
        ->assertSee('Cari pesanan Anda');
});

it('emails a signed access link when the order and email match', function () {
    Notification::fake();

    $order = makeGuestOrder(['order_number' => 'ORD-GUEST-1']);

    $this->post('/pesanan/cari', [
        'order_number' => 'ORD-GUEST-1',
        'email' => 'GUEST@example.com',
    ])->assertSessionHas('status');

    Notification::assertSentOnDemand(
        GuestOrderLink::class,
        function (GuestOrderLink $notification, array $channels, object $notifiable) use ($order) {
            return $notification->order->is($order)
                && $notifiable->routes['mail'] === 'guest@example.com';
        },
    );
});

it('returns a generic response without sending mail when details do not match', function () {
    Notification::fake();

    makeGuestOrder(['order_number' => 'ORD-GUEST-2']);

    $this->post('/pesanan/cari', [
        'order_number' => 'ORD-GUEST-2',
        'email' => 'wrong@example.com',
    ])->assertSessionHas('status');

    Notification::assertNothingSent();
});

it('throttles repeated guest lookup attempts', function () {
    Notification::fake();

    $order = makeGuestOrder(['order_number' => 'ORD-GUEST-3']);

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->post('/pesanan/cari', [
            'order_number' => $order->order_number,
            'email' => 'guest@example.com',
        ])->assertSessionHas('status');
    }

    $this->post('/pesanan/cari', [
        'order_number' => $order->order_number,
        'email' => 'guest@example.com',
    ])->assertSessionHasErrors('order_number');

    Notification::assertSentOnDemandTimes(GuestOrderLink::class, 10);
});

it('renders a guest order through a valid signed link', function () {
    $order = makeGuestOrder(['order_number' => 'ORD-SIGNED-1']);

    $this->get(GuestOrderAccess::urlFor($order))
        ->assertOk()
        ->assertSee('ORD-SIGNED-1')
        ->assertSee('Guest Buyer');
});

it('rejects expired signed links with a friendly page', function () {
    $order = makeGuestOrder(['order_number' => 'ORD-SIGNED-2']);

    $url = URL::temporarySignedRoute('guest.orders.show', now()->subMinute(), [
        'order' => $order->id,
    ]);

    $this->get($url)
        ->assertForbidden()
        ->assertSee('Tautan tidak valid atau kedaluwarsa');
});

it('rejects tampered signed links and direct unsigned access', function () {
    $order = makeGuestOrder(['order_number' => 'ORD-SIGNED-3']);

    $this->get(GuestOrderAccess::urlFor($order).'&tampered=1')->assertForbidden();
    $this->get(route('guest.orders.show', $order))->assertForbidden();
});
