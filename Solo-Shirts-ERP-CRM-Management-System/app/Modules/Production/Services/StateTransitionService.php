<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Events\OrderItemStateChanged;
use App\Modules\Production\Exceptions\ProductionException;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\States\ProductionState;
use Illuminate\Support\Facades\DB;

/**
 * Applies one production transition: lock the item, validate the edge against
 * the state machine, write an append-only audit row, move the state, and emit
 * the domain event. The whole thing is one transaction so concurrent attempts
 * serialize on the row lock — the loser re-evaluates against the new state and
 * is rejected as an invalid transition.
 */
final class StateTransitionService
{
    /**
     * A QC item may bounce to Rework at most this many times before a QC
     * Supervisor override (production.rework.override) is required.
     */
    public const MAX_REWORK_VISITS = 3;

    /**
     * The optional completed/rejected piece counts and attachment reference are
     * captured by the "complete stage" confirmation form (Kanban) and recorded on
     * the append-only ledger row. They are advisory metadata — they do not affect
     * the state-machine validation.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function transition(
        int $itemId,
        string $toState,
        User $actor,
        string $idempotencyKey,
        ?string $notes = null,
        array $metadata = [],
        ?int $completedQty = null,
        ?int $rejectedQty = null,
        ?string $attachmentPath = null,
        ?string $deliveryBoxCode = null,
    ): OrderItem {
        $targetClass = $this->resolveStateClass($toState);

        return DB::transaction(function () use ($itemId, $toState, $targetClass, $actor, $idempotencyKey, $notes, $metadata, $completedQty, $rejectedQty, $attachmentPath, $deliveryBoxCode): OrderItem {
            /** @var OrderItem $item */
            $item = OrderItem::query()->lockForUpdate()->findOrFail($itemId);

            $fromState = (string) $item->state;

            if (!$item->state->canTransitionTo($targetClass)) {
                throw ProductionException::invalidTransition($fromState, $toState);
            }

            $this->guardReworkLimit($item, $toState, $actor);

            $transition = ProductionTransition::query()->create([
                'order_item_id' => $item->id,
                'branch_id' => $item->branch_id,
                'from_state' => $fromState,
                'to_state' => $toState,
                'actor_id' => $actor->id,
                'idempotency_key' => $idempotencyKey,
                'notes' => $notes,
                'completed_qty' => $completedQty,
                'rejected_qty' => $rejectedQty,
                'attachment_path' => $attachmentPath,
                'metadata' => $metadata === [] ? null : $metadata,
                'occurred_at' => now(),
            ]);

            $attributes = ['state' => $toState];

            if ($toState === OrderItem::STATE_CANCELLED) {
                $attributes['cancelled_at'] = now();
                $attributes['cancel_reason'] = $notes;
            }

            // Record the pickup box / shelf when staging for delivery, so the Front
            // Desk can locate the package on collection. Only overwrite when given.
            if ($deliveryBoxCode !== null && $deliveryBoxCode !== '') {
                $attributes['delivery_box_code'] = $deliveryBoxCode;
            }

            $item->update($attributes);

            event(new OrderItemStateChanged(
                orderItemId: $item->id,
                from: $fromState,
                to: $toState,
                actorId: $actor->id,
                occurredAt: $transition->occurred_at,
                metadata: $metadata,
            ));

            return $item;
        });
    }

    /**
     * @return class-string<ProductionState>
     */
    private function resolveStateClass(string $toState): string
    {
        $class = ProductionState::resolveStateClass($toState);

        if ($class === null || !is_subclass_of($class, ProductionState::class)) {
            throw ProductionException::invalidTransition('?', $toState);
        }

        return $class;
    }

    private function guardReworkLimit(OrderItem $item, string $toState, User $actor): void
    {
        if ($toState !== OrderItem::STATE_REWORK) {
            return;
        }

        $visits = ProductionTransition::query()
            ->where('order_item_id', $item->id)
            ->where('to_state', OrderItem::STATE_REWORK)
            ->count();

        if ($visits >= self::MAX_REWORK_VISITS && !$actor->can('production.rework.override')) {
            throw ProductionException::reworkLimitExceeded();
        }
    }
}
