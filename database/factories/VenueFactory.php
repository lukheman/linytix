<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Hall',
            'slug' => fake()->unique()->slug(2),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Jakarta', 'Bandung', 'Surabaya', 'Makassar', 'Yogyakarta']),
            'province' => fake()->state(),
            'capacity' => fake()->numberBetween(100, 5000),
            'latitude' => fake()->latitude(-8, 5),
            'longitude' => fake()->longitude(95, 140),
            'maps_url' => null,
            'is_active' => true,
        ];
    }
}
