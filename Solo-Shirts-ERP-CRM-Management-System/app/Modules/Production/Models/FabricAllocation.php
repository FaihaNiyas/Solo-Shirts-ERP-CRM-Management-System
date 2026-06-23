<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Database\Factories\FabricAllocationFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_item_id
 * @property int $fabric_roll_id
 * @property int $branch_id
 * @property string $reserved_metres
 * @property string|null $consumed_metres
 * @property string $status
 * @property Carbon|null $reserved_at
 * @property int|null $reserved_by
 * @property Carbon|null $consumed_at
 * @property int|null $consumed_by
 * @property Carbon|null $released_at
 * @property int|null $released_by
 * @property string|null $release_reason
 * @property string $idempotency_key
 */
final class FabricAllocation extends Model
{
    /** @use HasFactory<FabricAllocationFactory> */
    use BelongsToBranch, HasFactory;

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_CONSUMED = 'consumed';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'order_item_id',
        'fabric_roll_id',
        'branch_id',
        'reserved_metres',
        'consumed_metres',
        'status',
        'reserved_at',
        'reserved_by',
        'consumed_at',
        'consumed_by',
        'released_at',
        'released_by',
        'release_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'reserved_metres' => 'decimal:2',
        'consumed_metres' => 'decimal:2',
        'reserved_at' => 'datetime',
        'consumed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<FabricRoll, $this>
     */
    public function roll(): BelongsTo
    {
        return $this->belongsTo(FabricRoll::class, 'fabric_roll_id');
    }

    protected static function newFactory(): FabricAllocationFactory
    {
        return FabricAllocationFactory::new();
    }
}
