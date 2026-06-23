<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Inventory\Models\FabricType;
use Illuminate\Database\Seeder;

final class FabricTypeSeeder extends Seeder
{
    /**
     * @var list<array{string, string, float}>
     */
    public const TYPES = [
        ['white', 'White', 30.0],
        ['black', 'Black', 3.0],
        ['navy', 'Navy', 3.0],
        ['grey', 'Grey', 3.0],
        ['cream', 'Cream', 3.0],
        ['sky_blue', 'Sky Blue', 3.0],
    ];

    public function run(): void
    {
        foreach (self::TYPES as [$code, $name, $threshold]) {
            FabricType::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'low_stock_threshold_metres' => $threshold, 'is_active' => true],
            );
        }
    }
}
