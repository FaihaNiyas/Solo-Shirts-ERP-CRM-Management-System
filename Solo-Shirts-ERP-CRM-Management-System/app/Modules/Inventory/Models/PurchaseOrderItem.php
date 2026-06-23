<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $fabric_type_id
 * @property string|null $colour
 * @property string $quantity_metres
 * @property int $unit_price_paise
 * @property string $received_metres
 */
final class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'fabric_type_id',
        'colour',
        'quantity_metres',
        'unit_price_paise',
        'received_metres',
    ];

    protected $casts = [
        'quantity_metres' => 'decimal:2',
        'unit_price_paise' => 'integer',
        'received_metres' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<FabricType, $this>
     */
    public function fabricType(): BelongsTo
    {
        return $this->belongsTo(FabricType::class);
    }

    protected static function newFactory(): PurchaseOrderItemFactory
    {
        return PurchaseOrderItemFactory::new();
    }
}
