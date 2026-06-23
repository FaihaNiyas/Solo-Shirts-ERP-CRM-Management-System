<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Modules\Production\Database\Factories\DefectCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 */
final class DefectCategory extends Model
{
    /** @use HasFactory<DefectCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): DefectCategoryFactory
    {
        return DefectCategoryFactory::new();
    }
}
