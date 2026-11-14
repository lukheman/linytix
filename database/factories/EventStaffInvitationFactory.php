<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventStaffInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventStaffInvitation>
 */
class EventStaffInvitationFactory extends Factory
{
    protected $model = EventStaffInvitation::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'email' => fake()->unique()->safeEmail(),
            'gate' => 'Gate Utama',
            'token_hash' => EventStaffInvitation::hashToken(Str::random(64)),
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
            'last_sent_at' => now(),
            'send_count' => 1,
        ];
    }

    public function withToken(string $rawToken): static
    {
        return $this->state(fn () => [
            'token_hash' => EventStaffInvitation::hashToken($rawToken),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'accepted_at' => now(),
            'accepted_by' => User::factory(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
        ]);
    }
}
