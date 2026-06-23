<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A customer post-delivery alteration request. Separate from internal QC rework.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $original_order_id
 * @property int $original_order_item_id
 * @property int|null $customer_id
 * @property int|null $requested_by_user_id
 * @property string $issue_type
 * @property string $issue_description
 * @property string $priority
 * @property bool $charge_required
 * @property int|null $estimated_charge_paise
 * @property string $status
 * @property string|null $photo_path
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 */
final class AlterationRequest extends Model
{
    use BelongsToBranch;

    public const STATUS_INTAKE = 'intake';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_ALTERATION = 'in_alteration';

    public const STATUS_READY = 'ready';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_URGENT = 'urgent';

    /**
     * Every valid alteration status (used for request validation).
     *
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_INTAKE,
        self::STATUS_APPROVED,
        self::STATUS_IN_ALTERATION,
        self::STATUS_READY,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    /**
     * The customer-alteration workflow (Phase 5B). Deliberately distinct from the
     * internal QC rework state machine — it never touches order_item.state.
     * delivered and cancelled are terminal (empty allow-list).
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        self::STATUS_INTAKE => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_IN_ALTERATION, self::STATUS_CANCELLED],
        self::STATUS_IN_ALTERATION => [self::STATUS_READY, self::STATUS_CANCELLED],
        self::STATUS_READY => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Suggested issue types (validated in the request; free of QC defect codes).
     *
     * @var list<string>
     */
    public const ISSUE_TYPES = [
        'fitting_issue',
        'stitching_issue',
        'length_adjustment',
        'fabric_issue',
        'button_zip_issue',
        'other',
    ];

    protected $fillable = [
        'branch_id',
        'original_order_id',
        'original_order_item_id',
        'customer_id',
        'requested_by_user_id',
        'issue_type',
        'issue_description',
        'priority',
        'charge_required',
        'estimated_charge_paise',
        'status',
        'photo_path',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'charge_required' => 'boolean',
        'estimated_charge_paise' => 'integer',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'original_order_id');
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'original_order_item_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * @return HasMany<AlterationStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(AlterationStatusLog::class)->latest('id');
    }

    /**
     * Statuses this request may legally move to from its current status.
     *
     * @return list<string>
     */
    public function allowedNextStatuses(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, $this->allowedNextStatuses(), true);
    }

    /** delivered / cancelled are terminal — no further transitions. */
    public function isFinal(): bool
    {
        return $this->allowedNextStatuses() === [];
    }
}
