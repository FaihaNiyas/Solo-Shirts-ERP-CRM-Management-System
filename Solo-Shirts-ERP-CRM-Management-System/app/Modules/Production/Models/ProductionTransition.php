<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Database\Factories\ProductionTransitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only audit row for one production state transition. The table has a
 * BEFORE UPDATE trigger that rejects any mutation, so rows are write-once. There
 * is no updated_at by design.
 *
 * @property int $id
 * @property int $order_item_id
 * @property int $branch_id
 * @property string|null $from_state
 * @property string $to_state
 * @property int|null $actor_id
 * @property string $idempotency_key
 * @property string|null $notes
 * @property int|null $completed_qty
 * @property int|null $rejected_qty
 * @property string|null $attachment_path
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
final class ProductionTransition extends Model
{
    /** @use HasFactory<ProductionTransitionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'order_item_id',
        'branch_id',
        'from_state',
        'to_state',
        'actor_id',
        'idempotency_key',
        'notes',
        'completed_qty',
        'rejected_qty',
        'attachment_path',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'completed_qty' => 'integer',
        'rejected_qty' => 'integer',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function newFactory(): ProductionTransitionFactory
    {
        return ProductionTransitionFactory::new();
    }
}
