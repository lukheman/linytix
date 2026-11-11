<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;
use App\Support\GuestOrderAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundSucceeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public Refund $refund,
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

        $url = $this->order->user_id
            ? route('akun.orders.show', $this->order)
            : GuestOrderAccess::urlFor($this->order);

        return (new MailMessage)
            ->subject('Refund pesanan '.$this->order->order_number.' berhasil')
            ->greeting('Halo '.$this->order->buyer_name.',')
            ->line('Refund untuk pesanan Anda telah berhasil diproses.')
            ->line('Order: '.$this->order->order_number)
            ->when($event, fn (MailMessage $mail) => $mail->line('Event: '.$event->title))
            ->line('Nominal refund: Rp '.number_format((int) $this->refund->amount, 0, ',', '.'))
            ->line('Dana akan dikembalikan ke metode pembayaran asal sesuai kebijakan penyedia pembayaran.')
            ->action('Lihat pesanan', $url)
            ->line('Terima kasih telah menggunakan layanan kami.');
    }
}
