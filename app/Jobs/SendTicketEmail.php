<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Mengirim e-tiket melalui email.
 *
 * Pada tahap ini job mencatat percobaan ke notification_logs. Integrasi mail
 * provider dan lampiran PDF ditambahkan pada tahap e-tiket.
 */
class SendTicketEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        NotificationLog::query()->create([
            'channel' => 'email',
            'recipient' => $order->buyer_email,
            'template' => 'ticket_issued',
            'status' => 'queued',
            'sent_at' => null,
        ]);
    }
}
