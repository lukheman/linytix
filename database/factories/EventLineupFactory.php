<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventLineup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventLineup>
 */
class EventLineupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->name(),
            'photo_url' => null,
            'perform_at' => fake()->dateTimeBetween('+3 days', '+3 months'),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
