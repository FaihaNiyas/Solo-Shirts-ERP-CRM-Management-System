<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Models;

use App\Models\User;
use App\Modules\Delivery\Database\Factories\DeliveryFactory;
use App\Modules\Order\Models\Order;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $branch_id
 * @property string $mode
 * @property string|null $address_snapshot
 * @property string|null $courier_partner
 * @property string|null $tracking_no
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $dispatched_at
 * @property Carbon|null $completed_at
 * @property string $status
 * @property int $delivery_charges_paise
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DeliveryAttempt> $attempts
 * @property-read Collection<int, DeliveryOtp> $otps
 */
final class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use BelongsToBranch, HasFactory;

    public const MODE_PICKUP = 'pickup';

    public const MODE_HOME = 'home_delivery';

    public const MODE_COURIER = 'courier';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_ATTEMPTED = 'attempted';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    public const MODES = [self::MODE_PICKUP, self::MODE_HOME, self::MODE_COURIER];

    /**
     * Terminal states from which no further action is allowed.
     *
     * @var list<string>
     */
    public const TERMINAL_STATUSES = [self::STATUS_DELIVERED, self::STATUS_CANCELLED];

    protected $fillable = [
        'order_id',
        'branch_id',
        'mode',
        'address_snapshot',
        'courier_partner',
        'tracking_no',
        'scheduled_at',
        'dispatched_at',
        'completed_at',
        'status',
        'delivery_charges_paise',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivery_charges_paise' => 'integer',
    ];

    public function isDispatched(): bool
    {
        return $this->dispatched_at !== null;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DeliveryAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    /**
     * @return HasMany<DeliveryOtp, $this>
     */
    public function otps(): HasMany
    {
        return $this->hasMany(DeliveryOtp::class);
    }

    protected static function newFactory(): DeliveryFactory
    {
        return DeliveryFactory::new();
    }
}
