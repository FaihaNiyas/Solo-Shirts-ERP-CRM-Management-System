<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Models;

use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $version_id
 * @property array<string, mixed> $fields_changed
 * @property array<string, mixed> $threshold_breached
 * @property int|null $acknowledged_by
 * @property Carbon|null $acknowledged_at
 */
final class MeasurementAlert extends Model
{
    use BelongsToBranch;

    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'version_id',
        'fields_changed',
        'threshold_breached',
        'acknowledged_by',
        'acknowledged_at',
        'created_at',
    ];

    protected $casts = [
        'fields_changed' => 'array',
        'threshold_breached' => 'array',
        'acknowledged_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<MeasurementVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(MeasurementVersion::class, 'version_id');
    }
}
