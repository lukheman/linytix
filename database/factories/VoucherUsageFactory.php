<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoucherUsage>
 */
class VoucherUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'order_id' => Order::factory(),
            'user_id' => null,
            'used_at' => now(),
        ];
    }
}
