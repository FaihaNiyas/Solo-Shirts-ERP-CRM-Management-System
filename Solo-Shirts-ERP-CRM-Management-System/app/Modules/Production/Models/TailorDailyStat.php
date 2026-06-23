<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $tailor_id
 * @property Carbon $on_date
 * @property int $bundles_completed
 * @property string $avg_minutes_per_piece
 * @property int $rework_count
 */
final class TailorDailyStat extends Model
{
    protected $fillable = [
        'branch_id',
        'tailor_id',
        'on_date',
        'bundles_completed',
        'avg_minutes_per_piece',
        'rework_count',
    ];

    protected $casts = [
        'on_date' => 'date',
        'bundles_completed' => 'integer',
        'avg_minutes_per_piece' => 'decimal:2',
        'rework_count' => 'integer',
    ];
}
