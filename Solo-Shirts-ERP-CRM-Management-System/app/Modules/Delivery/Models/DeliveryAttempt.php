<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Models;

use App\Models\User;
use App\Modules\Delivery\Database\Factories\DeliveryAttemptFactory;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delivery_id
 * @property int $branch_id
 * @property Carbon|null $attempted_at
 * @property int|null $attempted_by
 * @property string $reason_code
 * @property string|null $reason_notes
 */
final class DeliveryAttempt extends Model
{
    /** @use HasFactory<DeliveryAttemptFactory> */
    use AuditsChanges, BelongsToBranch, HasFactory;

    public const REASON_CUSTOMER_UNAVAILABLE = 'customer_unavailable';

    public const REASON_WRONG_ADDRESS = 'wrong_address';

    public const REASON_REFUSED = 'refused';

    public const REASON_PAYMENT_PENDING = 'payment_pending';

    public const REASON_OTHER = 'other';

    /**
     * @var list<string>
     */
    public const REASON_CODES = [
        self::REASON_CUSTOMER_UNAVAILABLE,
        self::REASON_WRONG_ADDRESS,
        self::REASON_REFUSED,
        self::REASON_PAYMENT_PENDING,
        self::REASON_OTHER,
    ];

    protected $fillable = [
        'delivery_id',
        'branch_id',
        'attempted_at',
        'attempted_by',
        'reason_code',
        'reason_notes',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        return ['delivery_id', 'reason_code'];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function attemptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attempted_by');
    }

    protected static function newFactory(): DeliveryAttemptFactory
    {
        return DeliveryAttemptFactory::new();
    }
}
