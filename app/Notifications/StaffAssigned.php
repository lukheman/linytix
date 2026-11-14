<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan bahwa akun yang sudah ada langsung ditugaskan sebagai staff.
 */
class StaffAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
        public string $gate,
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
        $timezone = $this->event->timezoneLabel();
        $schedule = $this->event->startsAtLocal()?->translatedFormat('l, d F Y, H.i');
        $name = $notifiable->name ?? 'Halo';

        return (new MailMessage)
            ->subject('Anda ditugaskan sebagai staff: '.$this->event->title)
            ->greeting('Halo '.$name.',')
            ->line('Anda telah ditugaskan sebagai staff pada event berikut.')
            ->line('Event: '.$this->event->title)
            ->when($schedule, fn (MailMessage $mail) => $mail->line('Jadwal: '.$schedule.' '.$timezone))
            ->when($this->event->venue?->name, fn (MailMessage $mail, $venue) => $mail->line('Lokasi: '.$venue))
            ->line('Gate penugasan: '.$this->gate)
            ->action('Buka halaman scan', route('scan.index'))
            ->line('Gunakan akun ini untuk memindai tiket di gate yang ditugaskan.')
            ->line('Jika Anda tidak merasa ditugaskan, hubungi organizer event.');
    }
}
