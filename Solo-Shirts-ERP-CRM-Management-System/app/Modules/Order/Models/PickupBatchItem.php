<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Finance\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A shirt inside a pickup batch (Phase 2). Snapshots the item's money picture at
 * batch creation and tracks what was paid within the batch and when it was
 * delivered.
 *
 * @property int $id
 * @property int $pickup_batch_id
 * @property int $order_item_id
 * @property int|null $invoice_line_id
 * @property int $item_total_paise
 * @property int $paid_before_paise
 * @property int $amount_due_paise
 * @property int $paid_in_batch_paise
 * @property Carbon|null $delivered_at
 * @property int|null $rack_slot_id
 */
final class PickupBatchItem extends Model
{
    protected $fillable = [
        'pickup_batch_id',
        'order_item_id',
        'invoice_line_id',
        'item_total_paise',
        'paid_before_paise',
        'amount_due_paise',
        'paid_in_batch_paise',
        'delivered_at',
        'rack_slot_id',
    ];

    protected $casts = [
        'item_total_paise' => 'integer',
        'paid_before_paise' => 'integer',
        'amount_due_paise' => 'integer',
        'paid_in_batch_paise' => 'integer',
        'delivered_at' => 'datetime',
        'rack_slot_id' => 'integer',
    ];

    /**
     * @return BelongsTo<PickupBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PickupBatch::class, 'pickup_batch_id');
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<InvoiceLine, $this>
     */
    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLine::class);
    }
}
