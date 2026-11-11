<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);
        $start = fake()->dateTimeBetween('+3 days', '+3 months');

        return [
            'organizer_id' => Organizer::factory(),
            'category_id' => Category::factory(),
            'venue_id' => Venue::factory(),
            'title' => rtrim($title, '.'),
            'short_desc' => fake()->sentence(),
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'description' => fake()->paragraphs(3, true),
            'terms' => 'Tiket yang sudah dibeli tidak dapat dikembalikan kecuali event dibatalkan.',
            'poster_url' => null,
            'banner_url' => null,
            'is_online' => false,
            'online_url' => null,
            'timezone' => 'Asia/Jakarta',
            'start_at' => $start,
            'doors_open_at' => (clone $start)->modify('-1 hour'),
            'end_at' => (clone $start)->modify('+3 hours'),
            'min_price' => 100000,
            'status' => Event::STATUS_PUBLISHED,
            'is_featured' => false,
            'require_identity' => false,
            'view_count' => 0,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => Event::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => [
            'status' => Event::STATUS_PENDING_REVIEW,
            'published_at' => null,
        ]);
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_SOLD_OUT]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'is_online' => true,
            'venue_id' => null,
            'online_url' => 'https://meet.example.test/'.Str::random(12),
        ]);
    }

    public function past(): static
    {
        return $this->state(function () {
            $start = fake()->dateTimeBetween('-3 months', '-1 day');

            return [
                'start_at' => $start,
                'doors_open_at' => (clone $start)->modify('-1 hour'),
                'end_at' => (clone $start)->modify('+3 hours'),
                'status' => Event::STATUS_ENDED,
            ];
        });
    }

    public function forOrganizer(Organizer $organizer): static
    {
        return $this->state(fn () => ['organizer_id' => $organizer->id]);
    }
}
