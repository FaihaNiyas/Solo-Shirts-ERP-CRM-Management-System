<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            OwnerUserSeeder::class,
            DefectCategorySeeder::class,
            FabricTypeSeeder::class,
            RackSlotSeeder::class,
        ]);
    }
}
