<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Database\Factories\TailorAssignmentFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $bundle_id
 * @property int $order_item_id
 * @property int $branch_id
 * @property int $tailor_id
 * @property int|null $assigned_by
 * @property Carbon|null $assigned_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property string $status
 * @property string|null $notes
 */
final class TailorAssignment extends Model
{
    /** @use HasFactory<TailorAssignmentFactory> */
    use BelongsToBranch, HasFactory;

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REASSIGNED = 'reassigned';

    protected $fillable = [
        'bundle_id',
        'order_item_id',
        'branch_id',
        'tailor_id',
        'assigned_by',
        'assigned_at',
        'started_at',
        'completed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isStarted(): bool
    {
        return $this->started_at !== null;
    }

    /**
     * @return BelongsTo<CutBundle, $this>
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CutBundle::class, 'bundle_id');
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
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tailor_id');
    }

    protected static function newFactory(): TailorAssignmentFactory
    {
        return TailorAssignmentFactory::new();
    }
}
