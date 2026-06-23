<?php

declare(strict_types=1);

namespace App\Modules\Order\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'branch_id' => Branch::factory(),
            'item_code' => 'ITEM-' . strtoupper(Str::random(6)),
            'product_type' => 'shirt',
            'quantity' => 1,
            'measurement_version_id' => MeasurementVersion::factory(),
            'state' => OrderItem::STATE_DRAFT,
        ];
    }

    public function inState(string $state): static
    {
        return $this->state(fn (array $attributes) => ['state' => $state]);
    }
}
