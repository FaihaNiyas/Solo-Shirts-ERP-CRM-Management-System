<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Models;

use App\Modules\Delivery\Database\Factories\DeliveryOtpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A one-time confirmation code. Only `otp_hash` is persisted — the plaintext is
 * never written to the database. `attempts` caps verification tries; `used_at`
 * marks the code as spent once a delivery is confirmed.
 *
 * @property int $id
 * @property int $delivery_id
 * @property string $otp_hash
 * @property Carbon $expires_at
 * @property int $attempts
 * @property Carbon|null $used_at
 */
final class DeliveryOtp extends Model
{
    /** @use HasFactory<DeliveryOtpFactory> */
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'otp_hash',
        'expires_at',
        'attempts',
        'used_at',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    protected static function newFactory(): DeliveryOtpFactory
    {
        return DeliveryOtpFactory::new();
    }
}
