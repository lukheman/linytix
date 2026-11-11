<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventImage>
 */
class EventImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'image_url' => fake()->imageUrl(1200, 800, 'concert'),
            'caption' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
