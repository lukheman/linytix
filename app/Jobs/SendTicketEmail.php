<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\Order;
use App\Notifications\TicketIssued;
use App\Support\GuestOrderAccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Mengirim e-tiket melalui email dan mencatat hasilnya ke notification_logs.
 */
class SendTicketEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->with(['event', 'tickets.ticketType'])->find($this->orderId);

        if (! $order || $order->status !== Order::STATUS_PAID) {
            return;
        }

        $log = NotificationLog::query()->create([
            'order_id' => $order->getKey(),
            'channel' => 'email',
            'recipient' => $order->buyer_email,
            'template' => 'ticket_issued',
            'status' => 'sending',
        ]);

        $url = $order->user_id
            ? route('akun.orders.show', $order)
            : GuestOrderAccess::urlFor($order);

        try {
            Notification::route('mail', $order->buyer_email)
                ->notifyNow(new TicketIssued($order, $url));

            $log->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $log->forceFill([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 1000),
            ])->save();

            throw $exception;
        }
    }
}
