<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $grn_id
 * @property int $purchase_order_item_id
 * @property int $fabric_roll_id
 * @property string $metres_received
 * @property Carbon|null $created_at
 */
final class GrnItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'grn_id',
        'purchase_order_item_id',
        'fabric_roll_id',
        'metres_received',
    ];

    protected $casts = [
        'metres_received' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Grn, $this>
     */
    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class, 'grn_id');
    }

    /**
     * @return BelongsTo<FabricRoll, $this>
     */
    public function roll(): BelongsTo
    {
        return $this->belongsTo(FabricRoll::class, 'fabric_roll_id');
    }
}
