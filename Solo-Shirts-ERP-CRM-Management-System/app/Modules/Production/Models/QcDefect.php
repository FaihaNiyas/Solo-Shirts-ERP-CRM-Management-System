<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $qc_inspection_id
 * @property int $defect_category_id
 * @property string $severity
 * @property string|null $notes
 * @property Carbon|null $created_at
 */
final class QcDefect extends Model
{
    public const UPDATED_AT = null;

    public const SEVERITY_MINOR = 'minor';

    public const SEVERITY_MAJOR = 'major';

    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'qc_inspection_id',
        'defect_category_id',
        'severity',
        'notes',
    ];

    /**
     * @return BelongsTo<QcInspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QcInspection::class, 'qc_inspection_id');
    }

    /**
     * @return BelongsTo<DefectCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DefectCategory::class, 'defect_category_id');
    }

    /**
     * @return HasMany<QcDefectPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(QcDefectPhoto::class, 'qc_defect_id');
    }
}
