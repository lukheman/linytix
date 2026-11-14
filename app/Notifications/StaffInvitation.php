<?php

namespace App\Notifications;

use App\Models\EventStaffInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Undangan untuk calon staff yang belum memiliki akun.
 */
class StaffInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EventStaffInvitation $invitation,
        public string $acceptUrl,
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
        $event = $this->invitation->event;
        $organizer = $event?->organizer;
        $timezone = $event?->timezoneLabel() ?? 'Asia/Jakarta';
        $schedule = $event?->startsAtLocal()?->translatedFormat('l, d F Y, H.i');
        $expires = $this->invitation->expires_at?->copy()->setTimezone($timezone);

        return (new MailMessage)
            ->subject('Undangan menjadi staff: '.($event?->title ?? 'Event LinyTix'))
            ->greeting('Halo,')
            ->line(($organizer?->name ?? 'Organizer').' mengundang Anda menjadi staff untuk event berikut.')
            ->line('Event: '.($event?->title ?? '-'))
            ->when($schedule, fn (MailMessage $mail) => $mail->line('Jadwal: '.$schedule.' '.$timezone))
            ->when($event?->venue?->name, fn (MailMessage $mail, $venue) => $mail->line('Lokasi: '.$venue))
            ->when($event?->is_online, fn (MailMessage $mail) => $mail->line('Acara daring'))
            ->line('Gate penugasan: '.$this->invitation->gate)
            ->action('Buat akun & terima undangan', $this->acceptUrl)
            ->line('Undangan berlaku sampai '.($expires?->translatedFormat('d F Y, H.i') ?? '-').' '.$timezone.'.')
            ->line('Jika Anda tidak mengenal organizer tersebut, abaikan email ini.');
    }
}
