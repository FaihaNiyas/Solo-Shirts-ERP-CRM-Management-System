<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Reporting\Models\DailyBranchStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyBranchStat>
 */
final class DailyBranchStatFactory extends Factory
{
    protected $model = DailyBranchStat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'on_date' => now()->toDateString(),
            'orders_received' => $this->faker->numberBetween(0, 50),
            'orders_delivered' => $this->faker->numberBetween(0, 50),
            'revenue_paise' => $this->faker->numberBetween(0, 10_000_000),
            'defects' => $this->faker->numberBetween(0, 20),
        ];
    }
}
