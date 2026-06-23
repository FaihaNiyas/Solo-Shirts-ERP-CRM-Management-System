<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A problem raised against a production item at a stage (Kanban Phase B). Parallel
 * to the state machine — an open issue flags the item without changing its state.
 *
 * @property int $id
 * @property int $order_item_id
 * @property int $branch_id
 * @property string $stage
 * @property string $issue_type
 * @property string $description
 * @property string $status
 * @property int|null $reported_by
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property string|null $resolution_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProductionIssue extends Model
{
    use BelongsToBranch;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    // Shop-floor issue taxonomy. 'other' lets staff describe anything not listed.
    public const TYPE_MATERIAL = 'material_defect';

    public const TYPE_MACHINE = 'machine_problem';

    public const TYPE_MEASUREMENT = 'measurement_issue';

    public const TYPE_QUALITY = 'quality_concern';

    public const TYPE_SHORTAGE = 'shortage';

    public const TYPE_OTHER = 'other';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_MATERIAL,
        self::TYPE_MACHINE,
        self::TYPE_MEASUREMENT,
        self::TYPE_QUALITY,
        self::TYPE_SHORTAGE,
        self::TYPE_OTHER,
    ];

    protected $fillable = [
        'order_item_id',
        'branch_id',
        'stage',
        'issue_type',
        'description',
        'status',
        'reported_by',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

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
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
