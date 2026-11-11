<?php

namespace Database\Factories;

use App\Models\Banner;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'image_url' => fake()->imageUrl(1600, 600, 'concert'),
            'link_url' => null,
            'sort_order' => fake()->numberBetween(0, 10),
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
