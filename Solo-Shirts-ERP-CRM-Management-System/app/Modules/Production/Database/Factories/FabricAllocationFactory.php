<?php

declare(strict_types=1);

namespace App\Modules\Production\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\FabricAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FabricAllocation>
 */
final class FabricAllocationFactory extends Factory
{
    protected $model = FabricAllocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'fabric_roll_id' => FabricRoll::factory(),
            'branch_id' => Branch::factory(),
            'reserved_metres' => fake()->randomFloat(2, 1, 5),
            'status' => FabricAllocation::STATUS_RESERVED,
            'reserved_at' => now(),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
