<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraph(),
            'logo_url' => null,
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'is_verified' => true,
            'verified_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }

    public function forOwner(User $user): static
    {
        return $this->state(fn () => ['owner_user_id' => $user->id]);
    }
}
