<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Goods Received Note.
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $branch_id
 * @property Carbon|null $received_at
 * @property int|null $received_by
 * @property string|null $notes
 */
final class Grn extends Model
{
    use BelongsToBranch;

    protected $table = 'grn';

    protected $fillable = [
        'purchase_order_id',
        'branch_id',
        'received_at',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return HasMany<GrnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class, 'grn_id');
    }
}
