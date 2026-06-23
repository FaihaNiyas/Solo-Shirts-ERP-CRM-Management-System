<?php

declare(strict_types=1);

namespace App\Modules\Production\Database\Factories;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Production\Models\QcDefectPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QcDefectPhoto>
 */
final class QcDefectPhotoFactory extends Factory
{
    protected $model = QcDefectPhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'disk' => 's3',
            'path' => 'qc-defects/' . Str::uuid() . '.jpg',
            'size_bytes' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
        ];
    }
}
