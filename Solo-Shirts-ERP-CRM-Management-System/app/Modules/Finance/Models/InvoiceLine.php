<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Database\Factories\InvoiceLineFactory;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $order_item_id
 * @property string $description
 * @property string|null $hsn_code
 * @property int $quantity
 * @property int $unit_price_paise
 * @property int $taxable_paise
 * @property string $gst_rate
 * @property int $tax_paise
 */
final class InvoiceLine extends Model
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'order_item_id',
        'description',
        'hsn_code',
        'quantity',
        'unit_price_paise',
        'taxable_paise',
        'gst_rate',
        'tax_paise',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_paise' => 'integer',
        'taxable_paise' => 'integer',
        'gst_rate' => 'decimal:2',
        'tax_paise' => 'integer',
    ];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    protected static function newFactory(): InvoiceLineFactory
    {
        return InvoiceLineFactory::new();
    }
}
