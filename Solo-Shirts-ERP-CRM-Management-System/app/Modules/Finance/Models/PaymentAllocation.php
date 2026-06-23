<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One slice of a payment attributed to an invoice line / order item (Phase 1).
 * Append-only: the table has BEFORE UPDATE/DELETE triggers, so there is no
 * updated_at semantics to rely on and the service only ever inserts.
 *
 * @property int $id
 * @property int $payment_id
 * @property int $invoice_id
 * @property int|null $invoice_line_id
 * @property int $order_id
 * @property int|null $order_item_id
 * @property int|null $pickup_batch_id
 * @property int $amount_paise
 * @property string $allocation_type
 * @property int $branch_id
 * @property int|null $created_by
 */
final class PaymentAllocation extends Model
{
    public const TYPE_ADVANCE = 'advance';

    public const TYPE_SELECTED_ITEM_BALANCE = 'selected_item_balance';

    public const TYPE_REMAINING_ITEMS_BALANCE = 'remaining_items_balance';

    public const TYPE_FULL_ORDER_BALANCE = 'full_order_balance';

    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'invoice_line_id',
        'order_id',
        'order_item_id',
        'pickup_batch_id',
        'amount_paise',
        'allocation_type',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'payment_id' => 'integer',
        'invoice_id' => 'integer',
        'invoice_line_id' => 'integer',
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'pickup_batch_id' => 'integer',
        'amount_paise' => 'integer',
        'branch_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<InvoiceLine, $this>
     */
    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLine::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
