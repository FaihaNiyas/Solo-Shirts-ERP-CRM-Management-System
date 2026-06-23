<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionIssue;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Builds the production kanban: order_items grouped by workflow state. Branch
 * isolation is automatic — the OrderItem global scope restricts the query to the
 * caller's active branch context. Intake-preparation orders are excluded — they
 * are not production work until the Front Desk confirms them.
 */
final class KanbanBoardService
{
    public function __construct(private readonly StageSupervisorService $supervisors) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, list<OrderItem>>
     */
    public function board(array $filters = []): array
    {
        $query = OrderItem::query()
            ->with([
                'order:id,order_code,customer_id,expected_delivery_date,priority,lifecycle_status',
                'order.customer:id,name',
                // Sibling ids (per order) so each card can show its "2 of 5" index
                // without an N+1. Loaded once per order, ordered by item_code so the
                // index matches the order the cards are displayed in.
                'order.items:id,order_id,item_code',
            ])
            // Card aggregates without an N+1: rework visits, open-issue count, and
            // the latest transition timestamp, computed in the same query.
            ->withCount([
                'transitions as rework_count' => fn (Builder $q): Builder => $q->where('to_state', OrderItem::STATE_REWORK),
                'issues as open_issues_count' => fn (Builder $q): Builder => $q->where('status', ProductionIssue::STATUS_OPEN),
            ])
            ->withMax('transitions as last_transition_at', 'occurred_at')
            ->whereHas('order', fn (Builder $q): Builder => $q->where('lifecycle_status', '!=', Order::LIFECYCLE_INTAKE))
            ->orderBy('item_code');

        if (isset($filters['order_id'])) {
            $query->where('order_id', (int) $filters['order_id']);
        }

        if (isset($filters['product_type'])) {
            $query->where('product_type', (string) $filters['product_type']);
        }

        // "My section" / supervisor scoping: restrict to a set of stages. An empty
        // list (a supervisor with no assignment) legitimately yields an empty board.
        if (isset($filters['stages']) && is_array($filters['stages'])) {
            $query->whereIn('state', $filters['stages']);
        }

        // Explicit single-stage / ready-only narrowing.
        if (isset($filters['stage'])) {
            $query->where('state', (string) $filters['stage']);
        }
        if (!empty($filters['ready'])) {
            $query->where('state', OrderItem::STATE_READY_FOR_DELIVERY);
        }

        // Items that have been through rework at least once.
        if (!empty($filters['rework'])) {
            $query->whereHas('transitions', fn (Builder $q): Builder => $q->where('to_state', OrderItem::STATE_REWORK));
        }

        if (isset($filters['priority'])) {
            $query->whereHas('order', fn (Builder $q): Builder => $q->where('priority', (string) $filters['priority']));
        }

        if (!empty($filters['delayed'])) {
            $query->whereHas('order', fn (Builder $q): Builder => $q->whereDate('expected_delivery_date', '<', now()->toDateString()));
        }
        if (isset($filters['date_from'])) {
            $query->whereHas('order', fn (Builder $q): Builder => $q->whereDate('expected_delivery_date', '>=', (string) $filters['date_from']));
        }
        if (isset($filters['date_to'])) {
            $query->whereHas('order', fn (Builder $q): Builder => $q->whereDate('expected_delivery_date', '<=', (string) $filters['date_to']));
        }

        // Free-text across item code, order code and customer name.
        if (isset($filters['search'])) {
            $term = (string) $filters['search'];
            $query->where(function (Builder $q) use ($term): void {
                $q->where('item_code', 'like', "%{$term}%")
                    ->orWhereHas('order', fn (Builder $o): Builder => $o->where('order_code', 'like', "%{$term}%"))
                    ->orWhereHas('order.customer', fn (Builder $c): Builder => $c->where('name', 'like', "%{$term}%"));
            });
        }

        /** @var array<string, list<OrderItem>> $columns */
        $columns = [];

        foreach (OrderItem::WORKFLOW_STATES as $state) {
            $columns[$state] = [];
        }

        // Stage => supervisor names for the active branch, attached to each card so
        // the board shows its assigned supervisor without an N+1.
        $supervisorMap = $this->supervisors->namesByStage();

        foreach ($query->get() as $item) {
            $state = (string) $item->state;
            $item->setAttribute('assigned_supervisors', $supervisorMap[$state] ?? []);
            $columns[$state][] = $item;
        }

        return $columns;
    }
}
