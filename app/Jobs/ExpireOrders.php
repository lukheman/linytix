<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\TicketType;
use App\Notifications\OrderExpired;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ExpireOrders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('expires_at')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $this->expireOrder($order);
                }
            });
    }

    /**
     * Kedaluwarsakan satu order secara idempoten. Dipakai job terjadwal dan
     * polling checkout agar status tidak bergantung pada worker yang berjalan.
     */
    public function expireOrder(Order $order): bool
    {
        $expired = false;

        DB::transaction(function () use ($order, &$expired): void {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            // Idempotensi: hanya order yang masih pending yang diproses.
            if (! $locked || $locked->status !== Order::STATUS_PENDING) {
                return;
            }

            foreach ($locked->items()->with('ticketType')->get() as $item) {
                if (! $item->ticketType) {
                    continue;
                }

                TicketType::query()
                    ->whereKey($item->ticketType->getKey())
                    ->update([
                        'sold_count' => max(0, $item->ticketType->sold_count - (int) $item->quantity),
                    ]);
            }

            $locked->forceFill(['status' => Order::STATUS_EXPIRED])->save();
            $expired = true;
        });

        if ($expired) {
            Notification::route('mail', $order->buyer_email)
                ->notify(new OrderExpired($order->loadMissing('event')));
        }

        return $expired;
    }
}
