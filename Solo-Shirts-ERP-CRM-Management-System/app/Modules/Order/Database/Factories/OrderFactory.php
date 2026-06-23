<?php

declare(strict_types=1);

namespace App\Modules\Order\Database\Factories;

use App\Modules\Customer\Models\Customer;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'order_code' => 'SSI-TST-ORD-' . strtoupper(Str::random(6)),
            'customer_id' => Customer::factory(),
            'source' => fake()->randomElement(['walk_in', 'phone', 'whatsapp', 'online']),
            'delivery_mode' => fake()->randomElement(['pickup', 'home', 'courier']),
            'delivery_charges_paise' => 0,
        ];
    }
}
