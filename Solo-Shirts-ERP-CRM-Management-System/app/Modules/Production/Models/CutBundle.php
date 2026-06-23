<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Database\Factories\CutBundleFactory;
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
 * @property string $bundle_code
 * @property int $pieces_count
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
final class CutBundle extends Model
{
    /** @use HasFactory<CutBundleFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'order_item_id',
        'fabric_roll_id',
        'branch_id',
        'bundle_code',
        'pieces_count',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'pieces_count' => 'integer',
    ];

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

    protected static function newFactory(): CutBundleFactory
    {
        return CutBundleFactory::new();
    }
}
