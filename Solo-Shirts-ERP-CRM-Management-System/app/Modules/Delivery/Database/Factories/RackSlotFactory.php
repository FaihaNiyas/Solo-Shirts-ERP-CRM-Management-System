<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Database\Factories;

use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RackSlot>
 */
final class RackSlotFactory extends Factory
{
    protected $model = RackSlot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'slot_code' => 'R-' . strtoupper(Str::random(5)),
            'label' => null,
            'is_active' => true,
            'current_order_item_id' => null,
        ];
    }
}
