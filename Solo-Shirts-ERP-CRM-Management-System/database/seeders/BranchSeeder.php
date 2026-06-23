<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Seeder;

final class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            ['code' => 'HQ'],
            [
                'name' => 'Head Office',
                'address' => 'Solo Shirts India HQ',
                'gst_number' => null,
                'phone' => null,
                'is_active' => true,
            ],
        );
    }
}
