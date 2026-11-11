<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => 'fake',
            'method' => fake()->randomElement(['qris', 'va_bca', 'gopay']),
            'gateway_ref' => 'PAY-'.strtoupper(Str::random(10)),
            'amount' => 100000,
            'status' => Payment::STATUS_PENDING,
            'payment_url' => null,
            'raw_response' => null,
            'paid_at' => null,
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);
    }
}
