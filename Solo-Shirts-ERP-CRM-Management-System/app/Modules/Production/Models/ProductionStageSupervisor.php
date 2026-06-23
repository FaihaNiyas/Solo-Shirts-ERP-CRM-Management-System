<?php

declare(strict_types=1);

namespace App\Modules\Production\Models;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A user's assignment as supervisor of a production section (stage) in a branch
 * (Kanban Phase C).
 *
 * @property int $id
 * @property int $branch_id
 * @property int $user_id
 * @property string $stage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProductionStageSupervisor extends Model
{
    use BelongsToBranch;

    /**
     * The production sections a supervisor can own — the workflow stages that
     * represent real shop-floor work (draft/delivered/cancelled are not sections).
     *
     * @var list<string>
     */
    public const SECTIONS = [
        OrderItem::STATE_FABRIC_ALLOCATED,
        OrderItem::STATE_CUTTING,
        OrderItem::STATE_TAILORING,
        OrderItem::STATE_KAJA_BUTTON,
        OrderItem::STATE_FINISHING,
        OrderItem::STATE_QC,
        OrderItem::STATE_REWORK,
        OrderItem::STATE_PACKING,
        OrderItem::STATE_READY_FOR_DELIVERY,
    ];

    protected $fillable = [
        'branch_id',
        'user_id',
        'stage',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
