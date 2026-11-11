<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1, 5) * 100000;

        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
            'user_id' => null,
            'event_id' => Event::factory(),
            'voucher_id' => null,
            'buyer_name' => fake()->name(),
            'buyer_email' => fake()->safeEmail(),
            'buyer_phone' => fake()->phoneNumber(),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'admin_fee' => 0,
            'payment_fee' => 0,
            'tax_amount' => 0,
            'total_amount' => $subtotal,
            'status' => Order::STATUS_PENDING,
            'expires_at' => now()->addMinutes(15),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => Order::STATUS_EXPIRED,
            'expires_at' => now()->subMinute(),
        ]);
    }
}
