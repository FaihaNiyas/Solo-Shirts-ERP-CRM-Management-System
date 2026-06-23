<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Database\Factories;

use App\Modules\Inventory\Models\FabricType;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
final class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'fabric_type_id' => FabricType::factory(),
            'colour' => fake()->safeColorName(),
            'quantity_metres' => fake()->randomFloat(2, 10, 100),
            'unit_price_paise' => fake()->numberBetween(10_000, 50_000),
            'received_metres' => 0,
        ];
    }
}
