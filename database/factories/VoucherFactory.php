<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PROMO'.strtoupper(Str::random(6)),
            'event_id' => Event::factory(),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'max_discount' => 100000,
            'min_purchase' => 0,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'used_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
