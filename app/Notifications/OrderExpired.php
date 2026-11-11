<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\GuestOrderAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderExpired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

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
            ->subject('Pesanan '.$this->order->order_number.' telah kedaluwarsa')
            ->greeting('Halo '.$this->order->buyer_name.',')
            ->line('Pesanan Anda belum dibayar sebelum batas waktu sehingga telah kedaluwarsa.')
            ->line('Order: '.$this->order->order_number)
            ->when($event, fn (MailMessage $mail) => $mail->line('Event: '.$event->title))
            ->action('Lihat pesanan', GuestOrderAccess::urlFor($this->order))
            ->line('Jika Anda masih ingin hadir, silakan buat pesanan baru selama tiket masih tersedia.');
    }
}
