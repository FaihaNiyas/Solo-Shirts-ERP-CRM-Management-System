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
 * @property int $id
 * @property int $branch_id
 * @property int $order_id
 * @property int $order_item_id
 * @property bool $checked_measurement_card
 * @property bool $checked_buttons
 * @property bool $checked_ironing
 * @property bool $checked_folded
 * @property bool $checked_packing_cover
 * @property bool $checked_label
 * @property int|null $packed_by
 * @property Carbon|null $packed_at
 * @property string|null $notes
 */
final class PackingChecklist extends Model
{
    use BelongsToBranch;

    /** Every box that must be ticked before an item can be marked packed. */
    public const REQUIRED_CHECKS = [
        'checked_measurement_card',
        'checked_buttons',
        'checked_ironing',
        'checked_folded',
        'checked_packing_cover',
        'checked_label',
    ];

    protected $fillable = [
        'branch_id',
        'order_id',
        'order_item_id',
        'checked_measurement_card',
        'checked_buttons',
        'checked_ironing',
        'checked_folded',
        'checked_packing_cover',
        'checked_label',
        'packed_by',
        'packed_at',
        'notes',
    ];

    protected $casts = [
        'checked_measurement_card' => 'boolean',
        'checked_buttons' => 'boolean',
        'checked_ironing' => 'boolean',
        'checked_folded' => 'boolean',
        'checked_packing_cover' => 'boolean',
        'checked_label' => 'boolean',
        'packed_at' => 'datetime',
    ];

    /** True once every required box is ticked. */
    public function isComplete(): bool
    {
        foreach (self::REQUIRED_CHECKS as $check) {
            if (!(bool) $this->{$check}) {
                return false;
            }
        }

        return true;
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
    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }
}
