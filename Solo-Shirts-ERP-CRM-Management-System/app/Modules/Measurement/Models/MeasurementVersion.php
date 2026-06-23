<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Models;

use App\Modules\Measurement\Database\Factories\MeasurementVersionFactory;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranchUnscoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Append-only versioned measurements. The measurement DATA (shirt_data,
 * pant_data, version_number, profile_id) is immutable after creation; only the
 * approval lifecycle fields (status, approved_*, effective_*) may change.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $profile_id
 * @property int $version_number
 * @property string $status
 * @property array<string, mixed>|null $shirt_data
 * @property array<string, mixed>|null $pant_data
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_to
 * @property array<string, mixed>|null $diff_json
 * @property bool $significant_change
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class MeasurementVersion extends Model
{
    /** @use HasFactory<MeasurementVersionFactory> */
    use AuditsChanges, BelongsToBranchUnscoped, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    private const IMMUTABLE = ['shirt_data', 'pant_data', 'version_number', 'profile_id', 'created_by'];

    protected $fillable = [
        'branch_id',
        'profile_id',
        'version_number',
        'status',
        'shirt_data',
        'pant_data',
        'effective_from',
        'effective_to',
        'diff_json',
        'significant_change',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'shirt_data' => 'array',
        'pant_data' => 'array',
        'diff_json' => 'array',
        'significant_change' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        return ['version_number', 'status'];
    }

    protected static function booted(): void
    {
        self::updating(function (self $version): void {
            foreach (self::IMMUTABLE as $field) {
                if ($version->isDirty($field)) {
                    throw new RuntimeException("measurement_versions.{$field} is immutable after creation.");
                }
            }
        });
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * @return BelongsTo<MeasurementProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(MeasurementProfile::class, 'profile_id');
    }

    protected static function newFactory(): MeasurementVersionFactory
    {
        return MeasurementVersionFactory::new();
    }
}
