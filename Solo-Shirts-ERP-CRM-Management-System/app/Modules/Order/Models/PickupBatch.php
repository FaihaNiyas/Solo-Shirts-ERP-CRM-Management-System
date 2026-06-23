<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One customer collection event for a subset of an order's ready items (Phase 2).
 * Branch-isolated via the global scope. V1 only supports pay-now / already-paid —
 * deferred (give-now-pay-later) is a future, permissioned phase and is not a
 * payment_mode value here.
 *
 * @property int $id
 * @property int $order_id
 * @property int $customer_id
 * @property int $branch_id
 * @property string $batch_no
 * @property string $pickup_type
 * @property string $payment_mode
 * @property string $status
 * @property int $total_paise
 * @property int $paid_paise
 * @property int $balance_paise
 * @property Carbon|null $handed_over_at
 * @property int|null $handed_over_by
 * @property string|null $receipt_no
 * @property int|null $created_by
 * @property-read Collection<int, PickupBatchItem> $items
 */
final class PickupBatch extends Model
{
    use AuditsChanges, BelongsToBranch;

    public const TYPE_COUNTER_PICKUP = 'counter_pickup';

    public const TYPE_HOME_DELIVERY = 'home_delivery';

    public const TYPE_COURIER = 'courier';

    public const PAYMENT_PAY_NOW = 'pay_now';

    public const PAYMENT_ALREADY_PAID = 'already_paid';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_HANDED_OVER = 'handed_over';

    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses that still hold the items (an item can't join a second such batch). */
    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PAYMENT_PENDING,
        self::STATUS_PAID,
        self::STATUS_HANDED_OVER,
    ];

    protected $fillable = [
        'order_id',
        'customer_id',
        'branch_id',
        'batch_no',
        'pickup_type',
        'payment_mode',
        'status',
        'total_paise',
        'paid_paise',
        'balance_paise',
        'handed_over_at',
        'handed_over_by',
        'receipt_no',
        'created_by',
    ];

    protected $casts = [
        'total_paise' => 'integer',
        'paid_paise' => 'integer',
        'balance_paise' => 'integer',
        'handed_over_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        return ['status', 'paid_paise', 'balance_paise', 'handed_over_at', 'receipt_no'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<PickupBatchItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PickupBatchItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }
}
