<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Database\Factories\PurchaseOrderFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $po_code
 * @property int $supplier_id
 * @property string $status
 * @property int $total_paise
 * @property string|null $notes
 * @property Carbon|null $placed_at
 * @property int|null $created_by
 * @property-read Collection<int, PurchaseOrderItem> $items
 */
final class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use BelongsToBranch, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PLACED = 'placed';

    public const STATUS_PARTIAL_RECEIVED = 'partial_received';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'branch_id',
        'po_code',
        'supplier_id',
        'status',
        'total_paise',
        'notes',
        'placed_at',
        'created_by',
    ];

    protected $casts = [
        'total_paise' => 'integer',
        'placed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    protected static function newFactory(): PurchaseOrderFactory
    {
        return PurchaseOrderFactory::new();
    }
}
