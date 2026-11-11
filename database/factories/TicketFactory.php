<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_code' => strtoupper(Str::random(32)),
            'order_item_id' => OrderItem::factory(),
            'order_id' => Order::factory(),
            'event_id' => Event::factory(),
            'ticket_type_id' => TicketType::factory(),
            'holder_name' => fake()->name(),
            'holder_id_number' => null,
            'status' => Ticket::STATUS_ACTIVE,
            'issued_at' => now(),
            'used_at' => null,
        ];
    }

    public function used(): static
    {
        return $this->state(fn () => [
            'status' => Ticket::STATUS_USED,
            'used_at' => now(),
        ]);
    }
}
