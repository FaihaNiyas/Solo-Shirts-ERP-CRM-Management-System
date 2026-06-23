<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Production\Models\DefectCategory;
use Illuminate\Database\Seeder;

final class DefectCategorySeeder extends Seeder
{
    /**
     * @var list<array{string, string}>
     */
    public const CATEGORIES = [
        ['stitch_open', 'Stitch Open'],
        ['color_mismatch', 'Colour Mismatch'],
        ['size_off', 'Size Off'],
        ['fabric_damage', 'Fabric Damage'],
        ['button_loose', 'Button Loose'],
        ['hem_uneven', 'Hem Uneven'],
        ['stain', 'Stain'],
        ['measurement_error', 'Measurement Error'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as [$code, $name]) {
            DefectCategory::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }
    }
}
