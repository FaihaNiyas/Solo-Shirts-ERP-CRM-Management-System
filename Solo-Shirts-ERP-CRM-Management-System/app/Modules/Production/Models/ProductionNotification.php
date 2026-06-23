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
 * One in-app production notification for one user (Kanban Phase F).
 *
 * @property int $id
 * @property int $branch_id
 * @property int $user_id
 * @property int|null $order_item_id
 * @property string $type
 * @property string $title
 * @property string|null $body
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 */
final class ProductionNotification extends Model
{
    use BelongsToBranch;

    public const UPDATED_AT = null;

    // Event types.
    public const TYPE_NEW_ASSIGNMENT = 'new_assignment';

    public const TYPE_ISSUE_REPORTED = 'issue_reported';

    public const TYPE_QC_FAILED = 'qc_failed';

    public const TYPE_DELAYED = 'delayed';

    public const TYPE_READY = 'ready_for_delivery';

    protected $fillable = [
        'branch_id',
        'user_id',
        'order_item_id',
        'type',
        'title',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
