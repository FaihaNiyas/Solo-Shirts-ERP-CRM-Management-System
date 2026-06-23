<?php

declare(strict_types=1);

namespace App\Modules\Production\Database\Factories;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductionTransition>
 */
final class ProductionTransitionFactory extends Factory
{
    protected $model = ProductionTransition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'branch_id' => Branch::factory(),
            'from_state' => OrderItem::STATE_DRAFT,
            'to_state' => OrderItem::STATE_FABRIC_ALLOCATED,
            'actor_id' => User::factory(),
            'idempotency_key' => (string) Str::uuid(),
            'notes' => null,
            'metadata' => null,
            'occurred_at' => now(),
        ];
    }
}
