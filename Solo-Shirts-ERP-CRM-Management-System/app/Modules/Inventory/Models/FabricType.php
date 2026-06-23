<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Database\Factories\FabricTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $low_stock_threshold_metres
 * @property bool $is_active
 */
final class FabricType extends Model
{
    /** @use HasFactory<FabricTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'low_stock_threshold_metres',
        'is_active',
    ];

    protected $casts = [
        'low_stock_threshold_metres' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): FabricTypeFactory
    {
        return FabricTypeFactory::new();
    }
}
