<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->randomElement([50000, 100000, 150000, 250000, 500000]);

        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Regular', 'VIP', 'Presale', 'Festival']),
            'description' => fake()->sentence(),
            'price' => $price,
            'quota' => fake()->numberBetween(50, 500),
            'sold_count' => 0,
            'min_per_order' => 1,
            'max_per_order' => 4,
            'sale_start_at' => null,
            'sale_end_at' => null,
            'is_active' => true,
        ];
    }

    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'sold_count' => $attributes['quota'] ?? 100,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn () => ['event_id' => $event->id]);
    }
}
