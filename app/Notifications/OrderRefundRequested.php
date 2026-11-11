<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRefundRequested extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Refund pesanan '.$this->order->order_number.' sedang diproses')
            ->greeting('Halo '.$this->order->buyer_name.',')
            ->line('Pembayaran Anda diterima setelah pesanan kedaluwarsa, namun stok tiket sudah tidak tersedia.')
            ->line('Kami telah membuat permintaan refund otomatis sebesar Rp '.number_format((int) $this->refund->amount, 0, ',', '.').'.')
            ->line('Tim kami akan memproses refund dan mengirimkan pembaruan berikutnya.')
            ->action('Lihat pesanan', route('guest.orders.lookup'))
            ->line('Jika ada pertanyaan, silakan hubungi tim dukungan kami.');
    }
}
