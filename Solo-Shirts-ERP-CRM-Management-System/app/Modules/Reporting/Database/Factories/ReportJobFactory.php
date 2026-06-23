<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Reporting\Models\ReportJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportJob>
 */
final class ReportJobFactory extends Factory
{
    protected $model = ReportJob::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'kind' => 'orders',
            'params' => [],
            'status' => ReportJob::STATUS_PENDING,
            'requested_at' => now(),
        ];
    }
}
