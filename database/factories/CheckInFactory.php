<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'event_id' => Event::factory(),
            'scanned_by' => null,
            'gate' => fake()->randomElement(['Gate A', 'Gate B', 'Main Entrance']),
            'result' => 'success',
            'scanned_at' => now(),
        ];
    }
}
