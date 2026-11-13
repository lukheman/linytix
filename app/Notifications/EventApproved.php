<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Event $event) {}

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

        $schedule = $this->event->startsAtLocal()?->translatedFormat('d M Y, H.i');

        return (new MailMessage)
            ->subject('Event "'.$this->event->title.'" telah disetujui')
            ->greeting('Halo '.$name.',')
            ->line('Selamat! Event Anda telah disetujui dan dipublikasikan.')
            ->line('Event: '.$this->event->title)
            ->when($schedule, fn (MailMessage $mail) => $mail->line(
                'Jadwal: '.$schedule.' '.$this->event->timezoneLabel(),
            ))
            ->line('Event sekarang dapat dilihat publik dan tiket dapat dijual.')
            ->action('Kelola event', route('organizer.events.index'))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }
}
