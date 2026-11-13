<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
        public string $reason,
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
        $organizer = $this->event->organizer;
        $name = $organizer?->owner?->name ?? $organizer?->name ?? 'Organizer';

        return (new MailMessage)
            ->subject('Event "'.$this->event->title.'" dibatalkan')
            ->greeting('Halo '.$name.',')
            ->line('Event berikut telah dibatalkan oleh admin.')
            ->line('Event: '.$this->event->title)
            ->line('Alasan pembatalan: '.$this->reason)
            ->line('Seluruh pesanan yang sudah dibayar akan diproses refund otomatis.')
            ->action('Lihat event', route('organizer.events.index'))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }
}
