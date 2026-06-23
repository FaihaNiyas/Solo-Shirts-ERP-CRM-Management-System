<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Order\Database\Factories\OrderFactory;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $order_code
 * @property int $customer_id
 * @property string $source
 * @property string $lifecycle_status
 * @property string $priority
 * @property string|null $channel_notes
 * @property Carbon|null $expected_delivery_date
 * @property string $delivery_mode
 * @property int $delivery_charges_paise
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_sum_total_paise
 */
final class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use AuditsChanges, BelongsToBranch, HasFactory, SoftDeletes;

    // Lifecycle gate (Phase 2.5) — distinct from the item-derived production status.
    public const LIFECYCLE_INTAKE = 'intake_preparation';

    public const LIFECYCLE_ORDER_RECEIVED = 'order_received';

    public const LIFECYCLE_CANCELLED = 'cancelled';

    // Production priority (Kanban). Order-level; every item inherits it.
    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    /** @var list<string> */
    public const PRIORITIES = [self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT];

    protected $fillable = [
        'branch_id',
        'order_code',
        'customer_id',
        'source',
        'lifecycle_status',
        'priority',
        'channel_notes',
        'expected_delivery_date',
        'delivery_mode',
        'delivery_charges_paise',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'delivery_charges_paise' => 'integer',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        return ['order_code', 'customer_id', 'delivery_mode', 'delivery_charges_paise'];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<PickupBatch, $this>
     */
    public function pickupBatches(): HasMany
    {
        return $this->hasMany(PickupBatch::class);
    }

    /**
     * Order value in paise. Prefers the authoritative invoice total once the order
     * is confirmed (uses the withSum/loadSum aggregate when present to avoid an
     * N+1), otherwise estimates from the per-item pricing captured at intake so the
     * list/detail still show a meaningful figure before invoicing.
     */
    public function computedTotalPaise(): int
    {
        $invoiceTotal = $this->invoices_sum_total_paise
            ?? ($this->relationLoaded('invoices') ? $this->invoices->sum('total_paise') : null);

        if ($invoiceTotal !== null && (int) $invoiceTotal > 0) {
            return (int) $invoiceTotal;
        }

        return (int) $this->items->sum(function (OrderItem $item): int {
            $design = is_array($item->design_notes) ? $item->design_notes : [];

            return (int) ($design['pricing']['taxable_paise'] ?? 0);
        });
    }

    public function isIntake(): bool
    {
        return $this->lifecycle_status === self::LIFECYCLE_INTAKE;
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
