<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Seeder;

final class RackSlotSeeder extends Seeder
{
    private const SLOTS_PER_BRANCH = 50;

    public function run(): void
    {
        Branch::query()->get()->each(function (Branch $branch): void {
            for ($i = 1; $i <= self::SLOTS_PER_BRANCH; $i++) {
                $code = sprintf('R-A-%02d', $i);

                RackSlot::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'slot_code' => $code],
                    ['label' => 'Rack A Slot ' . $i, 'is_active' => true],
                );
            }
        });
    }
}
