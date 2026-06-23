<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @mixin OrderItem
 */
final class ProductionItemResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->order;
        $state = (string) $this->state;
        $supervisors = is_array($this->assigned_supervisors) ? array_values(array_filter($this->assigned_supervisors)) : [];
        [$siblingIndex, $siblingCount] = $this->siblingPosition($order);

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $order?->order_code,
            'item_code' => $this->item_code,
            // Position of this sub-order within its parent order ("2 of 5"), so the
            // card can show one order is spread across several production stages.
            'sibling_index' => $siblingIndex,
            'sibling_count' => $siblingCount,
            'branch_id' => $this->branch_id,
            'product_type' => $this->product_type,
            'quantity' => $this->quantity,
            'measurement_version_id' => $this->measurement_version_id,
            'state' => $state,
            'allowed_transitions' => $this->state->transitionableStates(),

            // Card context (Kanban) — customer, priority, deadline, delay, holds, issues.
            'customer_name' => $order?->customer?->name,
            'priority' => $this->resolvePriority($order),
            'expected_delivery_date' => $order?->expected_delivery_date?->toDateString(),
            'is_overdue' => $this->isOverdue($order, $state),
            'overdue_days' => $this->overdueDays($order, $state),
            'is_on_hold' => $this->isOnHold(),
            'on_hold_reason' => $this->on_hold_reason,
            'delivery_box_code' => $this->delivery_box_code,
            'assigned_supervisor' => $supervisors[0] ?? null,
            'assigned_supervisors' => $supervisors,
            'rework_count' => $this->rework_count !== null ? (int) $this->rework_count : 0,
            'issue_count' => $this->open_issues_count !== null ? (int) $this->open_issues_count : 0,
            'note_preview' => $this->notePreview(),
            'last_transition_at' => $this->lastTransitionAt(),

            'cancelled_at' => $this->date($this->cancelled_at),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->date($this->created_at),
            'updated_at' => $this->date($this->updated_at),
        ];
    }

    /**
     * Authoritative order priority, falling back to the legacy per-item
     * design_notes->priority ('rush' → 'urgent') so pre-Kanban data still renders.
     */
    private function resolvePriority(?Order $order): string
    {
        $priority = $order?->priority;

        if ($priority === null || $priority === Order::PRIORITY_NORMAL) {
            $design = is_array($this->design_notes) ? $this->design_notes : [];
            if (($design['priority'] ?? null) === 'rush') {
                return Order::PRIORITY_URGENT;
            }
        }

        return $priority ?: Order::PRIORITY_NORMAL;
    }

    /**
     * This item's 1-based position among its order's sub-orders, plus the total.
     * Relies on the `order.items` relation being eager-loaded (the board does this);
     * returns [null, null] elsewhere so item-detail callers don't trigger an N+1.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function siblingPosition(?Order $order): array
    {
        if ($order === null || !$order->relationLoaded('items')) {
            return [null, null];
        }

        $ids = $order->items
            ->sortBy('item_code')
            ->pluck('id')
            ->values();

        $position = $ids->search($this->id);

        return [
            $position === false ? null : $position + 1,
            $ids->count(),
        ];
    }

    private function isOverdue(?Order $order, string $state): bool
    {
        $due = $order?->expected_delivery_date;
        if ($due === null) {
            return false;
        }

        $open = !in_array($state, [OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED], true);

        return $open && $due->lt(now()->startOfDay());
    }

    private function overdueDays(?Order $order, string $state): int
    {
        if (!$this->isOverdue($order, $state)) {
            return 0;
        }

        /** @var Carbon $due */
        $due = $order->expected_delivery_date;

        return (int) $due->diffInDays(now()->startOfDay());
    }

    private function notePreview(): ?string
    {
        $design = is_array($this->design_notes) ? $this->design_notes : [];
        $note = $design['notes'] ?? null;

        return is_string($note) && $note !== '' ? Str::limit($note, 80) : null;
    }

    /**
     * The most recent transition timestamp. Prefers the withMax-provided aggregate
     * (board query) and otherwise falls back to the item's own updated_at.
     */
    private function lastTransitionAt(): ?string
    {
        $value = $this->last_transition_at;

        if ($value !== null) {
            return $this->date(Carbon::parse((string) $value));
        }

        return $this->date($this->updated_at);
    }
}
