<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Link akses pesanan untuk pembeli guest (signed URL).
 */
class GuestOrderLink extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $url,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->order->event;

        return (new MailMessage)
            ->subject('Akses pesanan '.$this->order->order_number)
            ->greeting('Halo '.$this->order->buyer_name.',')
            ->line('Berikut tautan aman untuk melihat pesanan dan e-tiket Anda.')
            ->line('Order: '.$this->order->order_number)
            ->when($event, fn (MailMessage $mail) => $mail->line('Event: '.$event->title))
            ->action('Lihat pesanan & tiket', $this->url)
            ->line('Tautan ini bersifat pribadi. Jangan bagikan kepada orang lain.')
            ->line('Jika Anda tidak merasa meminta tautan ini, abaikan email ini.');
    }
}
