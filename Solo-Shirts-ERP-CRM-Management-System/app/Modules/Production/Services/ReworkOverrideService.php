<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Support\Str;

/**
 * Forces an item from QC back to Rework beyond the normal cap. The caller must
 * hold production.rework.override (enforced in the controller); the Phase 7
 * engine's own rework guard then passes because the actor can override.
 */
final class ReworkOverrideService
{
    public function __construct(private readonly StateTransitionService $transitions) {}

    public function override(int $itemId, ?string $notes, User $actor): OrderItem
    {
        $item = OrderItem::query()->findOrFail($itemId);

        $this->transitions->transition(
            $item->id,
            OrderItem::STATE_REWORK,
            $actor,
            (string) Str::uuid(),
            $notes ?? 'rework override',
            ['override' => true],
        );

        return $item->refresh();
    }
}
