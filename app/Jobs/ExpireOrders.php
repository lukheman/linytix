<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\TicketType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

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
                    $this->expire($order);
                }
            });
    }

    private function expire(Order $order): void
    {
        DB::transaction(function () use ($order): void {
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
        });
    }
}
