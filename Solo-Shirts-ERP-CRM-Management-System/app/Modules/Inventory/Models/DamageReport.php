<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Inventory\Database\Factories\DamageReportFactory;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Traits\AuditsChanges;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $fabric_roll_id
 * @property int|null $order_id
 * @property int|null $order_item_id
 * @property int|null $reported_by
 * @property string $stage
 * @property string $damage_type
 * @property string|null $damage_type_other
 * @property string $quantity_lost_metres
 * @property string|null $action_taken
 * @property string $status
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property int|null $rejected_by
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property Carbon|null $reported_at
 * @property-read Collection<int, DamageReportPhoto> $photos
 */
final class DamageReport extends Model
{
    /** @use HasFactory<DamageReportFactory> */
    use AuditsChanges, BelongsToBranch, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'branch_id',
        'fabric_roll_id',
        'order_id',
        'order_item_id',
        'reported_by',
        'stage',
        'damage_type',
        'damage_type_other',
        'quantity_lost_metres',
        'action_taken',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'reported_at',
    ];

    protected $casts = [
        'quantity_lost_metres' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'reported_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    protected function auditAttributes(): array
    {
        return ['status', 'quantity_lost_metres', 'damage_type'];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * @return BelongsTo<FabricRoll, $this>
     */
    public function roll(): BelongsTo
    {
        return $this->belongsTo(FabricRoll::class, 'fabric_roll_id');
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return HasMany<DamageReportPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(DamageReportPhoto::class);
    }

    protected static function newFactory(): DamageReportFactory
    {
        return DamageReportFactory::new();
    }
}
