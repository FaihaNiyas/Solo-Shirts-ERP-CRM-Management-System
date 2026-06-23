<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Models;

use App\Models\User;
use App\Modules\Delivery\Database\Factories\RackAssignmentFactory;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $rack_slot_id
 * @property int $order_item_id
 * @property int $branch_id
 * @property Carbon|null $assigned_at
 * @property int|null $assigned_by
 * @property Carbon|null $released_at
 * @property int|null $released_by
 * @property string|null $release_reason
 */
final class RackAssignment extends Model
{
    /** @use HasFactory<RackAssignmentFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'rack_slot_id',
        'order_item_id',
        'branch_id',
        'assigned_at',
        'assigned_by',
        'released_at',
        'released_by',
        'release_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->released_at === null;
    }

    /**
     * @return BelongsTo<RackSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(RackSlot::class, 'rack_slot_id');
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
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    protected static function newFactory(): RackAssignmentFactory
    {
        return RackAssignmentFactory::new();
    }
}
