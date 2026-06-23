<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A paused/in-progress Front Desk order wizard, persisted server-side so it can
 * be resumed from any device. Separate from a real order — see order_id for the
 * optional linked intake_preparation order.
 *
 * @property int $id
 * @property int $branch_id
 * @property int $user_id
 * @property int|null $customer_id
 * @property int|null $family_member_id
 * @property int|null $order_id
 * @property string|null $title
 * @property string $status
 * @property string|null $current_step
 * @property int $completed_count
 * @property int $total_items
 * @property array<string, mixed> $draft_payload
 * @property Carbon|null $last_saved_at
 * @property Carbon|null $converted_at
 * @property Carbon|null $discarded_at
 * @property Carbon|null $created_at
 */
final class FrontDeskOrderDraft extends Model
{
    use BelongsToBranch;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_DISCARDED = 'discarded';

    /** Statuses that still appear in the resume list. */
    public const OPEN_STATUSES = [self::STATUS_ACTIVE, self::STATUS_PAUSED];

    protected $fillable = [
        'branch_id',
        'user_id',
        'customer_id',
        'family_member_id',
        'order_id',
        'title',
        'status',
        'current_step',
        'completed_count',
        'total_items',
        'draft_payload',
        'last_saved_at',
        'converted_at',
        'discarded_at',
    ];

    protected $casts = [
        'draft_payload' => 'array',
        'completed_count' => 'integer',
        'total_items' => 'integer',
        'last_saved_at' => 'datetime',
        'converted_at' => 'datetime',
        'discarded_at' => 'datetime',
    ];

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
