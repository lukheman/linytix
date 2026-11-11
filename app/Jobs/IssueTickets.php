<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueTickets implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->with(['items.ticketType', 'event'])->find($this->orderId);

        if (! $order || $order->status !== Order::STATUS_PAID) {
            return;
        }

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $existing = Ticket::query()->where('order_item_id', $item->getKey())->count();
                $missing = max(0, $item->quantity - $existing);

                for ($i = 0; $i < $missing; $i++) {
                    Ticket::query()->create([
                        'ticket_code' => strtoupper(bin2hex(random_bytes(24))),
                        'order_item_id' => $item->getKey(),
                        'order_id' => $order->getKey(),
                        'event_id' => $order->event_id,
                        'ticket_type_id' => $item->ticket_type_id,
                        'holder_name' => $order->buyer_name,
                        'status' => Ticket::STATUS_ACTIVE,
                        'issued_at' => now(),
                    ]);
                }
            }
        });

        if (Str::length((string) $order->order_number) > 0) {
            SendTicketEmail::dispatch($order->getKey());
        }
    }
}
