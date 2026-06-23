<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Database\Factories;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricRoll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DamageReport>
 */
final class DamageReportFactory extends Factory
{
    protected $model = DamageReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'fabric_roll_id' => FabricRoll::factory(),
            'reported_by' => User::factory(),
            'stage' => 'cutting',
            'damage_type' => 'tear',
            'quantity_lost_metres' => fake()->randomFloat(2, 1, 5),
            'action_taken' => 'segregated for write-off',
            'status' => DamageReport::STATUS_PENDING,
            'reported_at' => now(),
        ];
    }
}
