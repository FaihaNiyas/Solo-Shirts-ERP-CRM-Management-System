<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Models;

use App\Modules\Delivery\Database\Factories\RackSlotFactory;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $slot_code
 * @property string|null $label
 * @property bool $is_active
 * @property int|null $current_order_item_id
 * @property Carbon|null $occupied_at
 */
final class RackSlot extends Model
{
    /** @use HasFactory<RackSlotFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'branch_id',
        'slot_code',
        'label',
        'is_active',
        'current_order_item_id',
        'occupied_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'occupied_at' => 'datetime',
    ];

    public function isOccupied(): bool
    {
        return $this->current_order_item_id !== null;
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function occupant(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'current_order_item_id');
    }

    protected static function newFactory(): RackSlotFactory
    {
        return RackSlotFactory::new();
    }
}
