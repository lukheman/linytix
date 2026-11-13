<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRejected extends Notification implements ShouldQueue
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
            ->subject('Event "'.$this->event->title.'" perlu diperbaiki')
            ->greeting('Halo '.$name.',')
            ->line('Event Anda belum dapat dipublikasikan karena belum memenuhi ketentuan review.')
            ->line('Event: '.$this->event->title)
            ->line('Alasan penolakan: '.$this->reason)
            ->line('Silakan perbaiki event sesuai catatan di atas, lalu ajukan review kembali.')
            ->action('Perbaiki event', route('organizer.events.edit', $this->event))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }
}
