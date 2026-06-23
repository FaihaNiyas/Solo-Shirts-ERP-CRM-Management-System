<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeasurementVersion>
 */
final class MeasurementVersionFactory extends Factory
{
    protected $model = MeasurementVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'profile_id' => MeasurementProfile::factory(),
            'version_number' => 1,
            'status' => MeasurementVersion::STATUS_APPROVED,
            'shirt_data' => ['chest' => 40, 'waist' => 34, 'shirt_length' => 29],
            'pant_data' => null,
            'effective_from' => now(),
            'significant_change' => false,
            'created_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MeasurementVersion::STATUS_PENDING,
            'effective_from' => null,
            'significant_change' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MeasurementVersion::STATUS_REJECTED,
            'effective_from' => null,
            'rejection_reason' => 'Out of acceptable range.',
        ]);
    }
}
