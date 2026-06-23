<?php

declare(strict_types=1);

namespace App\Modules\Production\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\CutBundle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CutBundle>
 */
final class CutBundleFactory extends Factory
{
    protected $model = CutBundle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'BND-' . strtoupper(Str::random(8));

        return [
            'order_item_id' => OrderItem::factory(),
            'fabric_roll_id' => FabricRoll::factory(),
            'branch_id' => Branch::factory(),
            'bundle_code' => $code,
            'pieces_count' => fake()->numberBetween(1, 12),
        ];
    }
}
