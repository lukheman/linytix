<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\TicketPdf;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketIssued extends Notification
{
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
        $tickets = $this->order->tickets;

        $mail = (new MailMessage)
            ->subject('E-tiket '.$this->order->order_number)
            ->greeting('Halo '.$this->order->buyer_name.',')
            ->line('Pembayaran Anda telah dikonfirmasi. E-tiket berikut sudah aktif.')
            ->line('Order: '.$this->order->order_number)
            ->when($event, fn (MailMessage $message) => $message->line('Event: '.$event->title))
            ->when($event?->startsAtLocal(), fn (MailMessage $message, $start) => $message->line('Jadwal: '.$start->translatedFormat('l, d F Y, H.i').' WIB'));

        foreach ($tickets as $ticket) {
            $mail->line('• '.($ticket->ticketType?->name ?? 'Tiket').' — '.$ticket->ticket_code);
        }

        return $mail
            ->action('Buka e-tiket', $this->url)
            ->attachData(
                TicketPdf::output($this->order),
                'e-tiket-'.$this->order->order_number.'.pdf',
                ['mime' => 'application/pdf'],
            )
            ->line('Tunjukkan QR pada e-tiket saat memasuki lokasi event.')
            ->line('Jangan bagikan tautan atau kode tiket kepada orang lain.');
    }
}
