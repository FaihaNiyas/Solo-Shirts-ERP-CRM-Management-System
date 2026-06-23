<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Database\Factories;

use App\Modules\Delivery\Models\Delivery;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
final class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'branch_id' => Branch::factory(),
            'mode' => Delivery::MODE_HOME,
            'address_snapshot' => null,
            'status' => Delivery::STATUS_SCHEDULED,
            'delivery_charges_paise' => 0,
        ];
    }

    public function dispatched(): self
    {
        return $this->state(fn (): array => [
            'status' => Delivery::STATUS_DISPATCHED,
            'dispatched_at' => now(),
        ]);
    }

    public function courier(): self
    {
        return $this->state(fn (): array => [
            'mode' => Delivery::MODE_COURIER,
            'courier_partner' => 'BlueDart',
            'tracking_no' => 'BD' . $this->faker->numberBetween(100000, 999999),
        ]);
    }
}
