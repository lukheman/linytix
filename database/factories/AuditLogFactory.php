<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'verified', 'refunded']),
            'entity' => fake()->randomElement(['event', 'order', 'organizer', 'user']),
            'entity_id' => fake()->uuid(),
            'meta' => [],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
