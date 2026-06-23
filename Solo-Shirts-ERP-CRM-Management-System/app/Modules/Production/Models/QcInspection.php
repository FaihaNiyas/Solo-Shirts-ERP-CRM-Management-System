<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Database\Factories\QcInspectionFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int $branch_id
 * @property int $attempt_number
 * @property int|null $previous_inspection_id
 * @property string $disposition
 * @property string|null $failure_reason
 * @property string|null $failure_stage
 * @property string|null $rework_target_stage
 * @property int|null $inspector_id
 * @property string|null $notes
 * @property Carbon|null $inspected_at
 * @property-read Collection<int, QcDefect> $defects
 */
final class QcInspection extends Model
{
    /** @use HasFactory<QcInspectionFactory> */
    use BelongsToBranch, HasFactory;

    public const DISPOSITION_PASS = 'pass';

    public const DISPOSITION_PASS_WITH_NOTE = 'pass_with_note';

    public const DISPOSITION_REWORK = 'rework';

    public const DISPOSITION_REJECT = 'reject';

    /** Phase 7C — structured QC failure reasons (shop-floor enum). */
    public const FAILURE_REASONS = [
        'measurement_mismatch',
        'stitching_issue',
        'fabric_damage',
        'stain',
        'button_issue',
        'finishing_issue',
        'wrong_style',
        'other',
    ];

    /** Stages an item may be routed back to for internal rework. */
    public const REWORK_TARGET_STAGES = [
        OrderItem::STATE_CUTTING,
        OrderItem::STATE_TAILORING,
        OrderItem::STATE_KAJA_BUTTON,
        OrderItem::STATE_FINISHING,
    ];

    /** Dispositions that count as a passing inspection. */
    public const PASSED_DISPOSITIONS = [self::DISPOSITION_PASS, self::DISPOSITION_PASS_WITH_NOTE];

    protected $fillable = [
        'order_item_id',
        'branch_id',
        'attempt_number',
        'previous_inspection_id',
        'disposition',
        'failure_reason',
        'failure_stage',
        'rework_target_stage',
        'inspector_id',
        'notes',
        'inspected_at',
    ];

    /** Derived pass/fail summary (Phase 7C `result`). */
    public function result(): string
    {
        return in_array($this->disposition, self::PASSED_DISPOSITIONS, true) ? 'passed' : 'failed';
    }

    protected $casts = [
        'attempt_number' => 'integer',
        'inspected_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * @return HasMany<QcDefect, $this>
     */
    public function defects(): HasMany
    {
        return $this->hasMany(QcDefect::class);
    }

    protected static function newFactory(): QcInspectionFactory
    {
        return QcInspectionFactory::new();
    }
}
