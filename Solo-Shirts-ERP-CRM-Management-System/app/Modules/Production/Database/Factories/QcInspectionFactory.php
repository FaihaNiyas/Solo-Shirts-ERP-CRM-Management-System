<?php

declare(strict_types=1);

namespace App\Modules\Production\Database\Factories;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\QcInspection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QcInspection>
 */
final class QcInspectionFactory extends Factory
{
    protected $model = QcInspection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'branch_id' => Branch::factory(),
            'attempt_number' => 1,
            'disposition' => QcInspection::DISPOSITION_PASS,
            'inspector_id' => User::factory(),
            'inspected_at' => now(),
        ];
    }
}
