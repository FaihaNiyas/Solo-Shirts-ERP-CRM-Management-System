<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line in the append-only fabric stock ledger. `metres` is a positive
 * magnitude; direction is derived from `type`. No updated_at by design.
 *
 * @property int $id
 * @property int $fabric_roll_id
 * @property int $branch_id
 * @property string $type
 * @property string $metres
 * @property string|null $reason
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $idempotency_key
 * @property int|null $actor_id
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
final class FabricMovement extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_RECEIVE = 'receive';

    public const TYPE_RESERVE = 'reserve';

    public const TYPE_RELEASE = 'release';

    public const TYPE_OUT = 'out';

    public const TYPE_ADJUST_IN = 'adjust_in';

    public const TYPE_ADJUST_OUT = 'adjust_out';

    public const TYPE_DAMAGE_WRITEOFF = 'damage_writeoff';

    /**
     * Types that increase remaining_metres.
     *
     * @var list<string>
     */
    public const ADDITIONS = [self::TYPE_RECEIVE, self::TYPE_ADJUST_IN];

    /**
     * Types that decrease remaining_metres.
     *
     * @var list<string>
     */
    public const DEDUCTIONS = [self::TYPE_OUT, self::TYPE_ADJUST_OUT, self::TYPE_DAMAGE_WRITEOFF];

    protected $fillable = [
        'fabric_roll_id',
        'branch_id',
        'type',
        'metres',
        'reason',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'actor_id',
        'occurred_at',
    ];

    protected $casts = [
        'metres' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<FabricRoll, $this>
     */
    public function roll(): BelongsTo
    {
        return $this->belongsTo(FabricRoll::class, 'fabric_roll_id');
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
}
