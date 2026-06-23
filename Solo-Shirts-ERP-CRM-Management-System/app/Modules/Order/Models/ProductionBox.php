<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A physical production box. Holds cut material + the printed job card for ONE
 * sub-order. Distinct from delivery `rack_slots` (ready-for-pickup staging).
 *
 * @property int $id
 * @property int $branch_id
 * @property string $box_code
 * @property string $status
 * @property int|null $current_order_item_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $released_at
 */
final class ProductionBox extends Model
{
    use BelongsToBranch, SoftDeletes;

    public const STATUS_FREE = 'free';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'branch_id',
        'box_code',
        'status',
        'current_order_item_id',
        'assigned_at',
        'released_at',
    ];

    protected $casts = [
        'current_order_item_id' => 'integer',
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function currentItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'current_order_item_id');
    }

    public function isFree(): bool
    {
        return $this->status === self::STATUS_FREE && $this->current_order_item_id === null;
    }

    public function isOccupiedByOther(int $itemId): bool
    {
        return $this->current_order_item_id !== null
            && $this->current_order_item_id !== $itemId;
    }
}
