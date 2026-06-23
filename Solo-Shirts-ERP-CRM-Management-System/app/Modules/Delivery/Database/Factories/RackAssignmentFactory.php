<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Database\Factories;

use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RackAssignment>
 */
final class RackAssignmentFactory extends Factory
{
    protected $model = RackAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rack_slot_id' => RackSlot::factory(),
            'order_item_id' => OrderItem::factory(),
            'branch_id' => Branch::factory(),
            'assigned_at' => now(),
        ];
    }
}
