<?php

declare(strict_types=1);

namespace App\Modules\Production\Database\Factories;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\CutBundle;
use App\Modules\Production\Models\TailorAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TailorAssignment>
 */
final class TailorAssignmentFactory extends Factory
{
    protected $model = TailorAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bundle_id' => CutBundle::factory(),
            'order_item_id' => OrderItem::factory(),
            'branch_id' => Branch::factory(),
            'tailor_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'status' => TailorAssignment::STATUS_ASSIGNED,
        ];
    }

    public function started(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TailorAssignment::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TailorAssignment::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
