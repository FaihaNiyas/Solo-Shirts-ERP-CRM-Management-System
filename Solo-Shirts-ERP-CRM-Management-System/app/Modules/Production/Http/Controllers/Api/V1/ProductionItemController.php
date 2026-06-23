<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Inventory\Http\Resources\DamageReportListResource;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Resources\FabricAllocationResource;
use App\Modules\Production\Http\Resources\PackingChecklistResource;
use App\Modules\Production\Http\Resources\ProductionIssueResource;
use App\Modules\Production\Http\Resources\ProductionItemResource;
use App\Modules\Production\Http\Resources\ProductionTransitionResource;
use App\Modules\Production\Http\Resources\QcInspectionResource;
use App\Modules\Production\Models\FabricAllocation;
use App\Modules\Production\Models\PackingChecklist;
use App\Modules\Production\Models\ProductionIssue;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\Models\QcInspection;
use App\Modules\Production\Policies\ProductionPolicy;
use App\Modules\Production\Services\StageSupervisorService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Production shop-floor read APIs (Phase 7A). Everything is branch-scoped via the
 * global scope and excludes intake_preparation drafts and cancelled items, so the
 * production team only ever sees confirmed, in-flight garments.
 */
final class ProductionItemController extends BaseApiController
{
    private const QUEUE_LIMIT = 100;

    /** Flat, filterable production queue. */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrderItem::class);

        $query = $this->baseQuery();

        if ($request->filled('stage')) {
            $query->where('state', (string) $request->string('stage'));
        }
        if ($request->filled('product_type')) {
            $query->where('product_type', (string) $request->string('product_type'));
        }
        if ($request->filled('item_code')) {
            $query->where('item_code', 'like', '%' . $request->string('item_code') . '%');
        }
        if ($request->filled('box_code')) {
            $query->where('box_code', (string) $request->string('box_code'));
        }
        if ($request->boolean('rush')) {
            $query->where('design_notes->priority', 'rush');
        }
        if ($request->filled('order_code')) {
            $code = (string) $request->string('order_code');
            $query->whereHas('order', fn (Builder $o): Builder => $o->where('order_code', 'like', "%{$code}%"));
        }
        if ($request->filled('due')) {
            $due = (string) $request->string('due');
            $query->whereHas('order', fn (Builder $o): Builder => $o->whereDate('expected_delivery_date', $due));
        }

        $items = $query->orderBy('item_code')->limit(self::QUEUE_LIMIT)->get();
        $lastByItem = $this->lastTransitionMap($items->pluck('id')->all());
        $fabricByItem = $this->fabricStatusMap($items->pluck('id')->all());

        return $this->respond($items->map(fn (OrderItem $i): array => $this->row($i, $lastByItem[$i->id] ?? null, $fabricByItem[$i->id] ?? 'none'))->all());
    }

    /** Full sub-order workbench. */
    public function show(Request $request, OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        $item->loadMissing(['order.customer:id,name', 'measurementVersion.profile:id,name']);
        $item->loadCount(['issues as open_issues_count' => fn (Builder $q): Builder => $q->where('status', ProductionIssue::STATUS_OPEN)]);
        $item->setAttribute('assigned_supervisors', app(StageSupervisorService::class)->namesForStage((string) $item->state));

        /** @var User|null $actor */
        $actor = $request->user();

        $base = (new ProductionItemResource($item))->resolve();
        $last = $this->lastTransitionMap([$item->id])[$item->id] ?? null;

        return $this->respond($base + $this->row($item, $last, $this->fabricStatusMap([$item->id])[$item->id] ?? 'none') + [
            'allowed_next_stages' => $this->allowedNextStages($item, $actor),
            'measurement_profile' => $item->measurementVersion?->profile?->name,
            'measurement' => $this->measurementValues($item),
            'notes' => $this->design($item)['notes'] ?? null,
            'fabric_allocation' => $this->fabricAllocation($item),
            'cloth_damage' => $this->clothDamage($item),
            'qc' => $this->qcSummary($item),
            'packing' => $this->packingSummary($item),
            'issues' => $this->issuesSummary($item),
        ]);
    }

    /**
     * Open + resolved production issues for the workbench (Kanban Phase B).
     *
     * @return array<string, mixed>
     */
    private function issuesSummary(OrderItem $item): array
    {
        $issues = ProductionIssue::query()
            ->where('order_item_id', $item->id)
            ->with(['reporter:id,name', 'resolver:id,name'])
            ->latest('id')
            ->get();

        return [
            'open_count' => $issues->where('status', ProductionIssue::STATUS_OPEN)->count(),
            'total' => $issues->count(),
            'list' => ProductionIssueResource::collection($issues)->resolve(),
        ];
    }

    public function history(OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        $transitions = ProductionTransition::query()
            ->where('order_item_id', $item->id)
            ->with('actor:id,name')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        return $this->respond(ProductionTransitionResource::collection($transitions)->resolve());
    }

    /** Resolve a production item by typing/pasting its item code, box code or order code. */
    public function searchByCode(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrderItem::class);

        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return $this->respond([]);
        }

        $items = $this->baseQuery()
            ->where(function (Builder $sub) use ($q): void {
                $sub->where('item_code', $q)
                    ->orWhere('box_code', $q)
                    ->orWhereHas('order', fn (Builder $o): Builder => $o->where('order_code', $q));
            })
            ->orderBy('item_code')
            ->limit(self::QUEUE_LIMIT)
            ->get();

        $lastByItem = $this->lastTransitionMap($items->pluck('id')->all());
        $fabricByItem = $this->fabricStatusMap($items->pluck('id')->all());

        return $this->respond($items->map(fn (OrderItem $i): array => $this->row($i, $lastByItem[$i->id] ?? null, $fabricByItem[$i->id] ?? 'none'))->all());
    }

    /**
     * Confirmed, non-cancelled production items only.
     *
     * @return Builder<OrderItem>
     */
    private function baseQuery(): Builder
    {
        return OrderItem::query()
            ->with(['order:id,order_code,customer_id,expected_delivery_date,lifecycle_status', 'order.customer:id,name', 'measurementVersion:id,version_number,profile_id'])
            ->whereHas('order', fn (Builder $o): Builder => $o->where('lifecycle_status', '!=', Order::LIFECYCLE_INTAKE))
            ->where('state', '!=', OrderItem::STATE_CANCELLED);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(OrderItem $item, ?string $lastTransitionAt, string $fabricStatus = 'none'): array
    {
        $design = $this->design($item);

        return [
            'fabric_status' => $fabricStatus,
            'item_id' => $item->id,
            'item_code' => $item->item_code,
            'order_id' => $item->order_id,
            'order_code' => $item->order?->order_code,
            'product_type' => $item->product_type,
            'customer_name' => $item->order?->customer?->name,
            'delivery_date' => $item->order?->expected_delivery_date?->toDateString(),
            'priority' => $design['priority'] ?? 'regular',
            'is_rush' => ($design['priority'] ?? '') === 'rush',
            'current_stage' => (string) $item->state,
            'production_box_code' => $item->box_code,
            'placed_in_box' => (bool) $item->placed_in_box,
            'fabric' => $design['fabric'] ?? $item->fabric_preference_text,
            'style' => $design['style'] ?? null,
            'fit' => $design['fit'] ?? null,
            'measurement_version_id' => $item->measurement_version_id,
            'measurement_version' => $item->measurementVersion?->version_number,
            'job_card_url' => $item->order_id !== null ? "/api/v1/orders/{$item->order_id}/items/{$item->id}/job-card" : null,
            'last_transition_at' => $lastTransitionAt,
            'blockers' => $this->blockers($item),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function design(OrderItem $item): array
    {
        return is_array($item->design_notes) ? $item->design_notes : [];
    }

    /**
     * The non-empty measurement values for this item's product type.
     *
     * @return array<string, mixed>
     */
    private function measurementValues(OrderItem $item): array
    {
        $version = $item->measurementVersion;
        if ($version === null) {
            return [];
        }

        $values = $item->product_type === 'pant' ? ($version->pant_data ?? []) : ($version->shirt_data ?? []);

        return collect($values)
            ->filter(fn ($v, $k): bool => $v !== null && $v !== '' && !str_starts_with((string) $k, 'note_'))
            ->all();
    }

    /**
     * Workflow-allowed next states filtered to those the current user may perform.
     *
     * @return list<string>
     */
    private function allowedNextStages(OrderItem $item, ?User $actor): array
    {
        if ($actor === null) {
            return [];
        }

        return array_values(array_filter(
            $item->state->transitionableStates(),
            function (string $state) use ($actor): bool {
                $permission = ProductionPolicy::TRANSITION_PERMISSIONS[$state] ?? null;

                return $permission !== null && $actor->can($permission);
            },
        ));
    }

    /**
     * Missing prerequisites for production (advisory — order confirm already
     * guarantees these for real orders).
     *
     * @return list<string>
     */
    private function blockers(OrderItem $item): array
    {
        $blockers = [];
        if ($item->measurement_version_id === null) {
            $blockers[] = 'no_measurement';
        }

        return $blockers;
    }

    /**
     * @param  list<int>  $itemIds
     * @return array<int, string>
     */
    private function lastTransitionMap(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return ProductionTransition::query()
            ->whereIn('order_item_id', $itemIds)
            ->selectRaw('order_item_id, MAX(occurred_at) as last_at')
            ->groupBy('order_item_id')
            ->pluck('last_at', 'order_item_id')
            ->map(fn ($v): string => (string) $v)
            ->all();
    }

    /**
     * The latest active (reserved/consumed) allocation status per item, in one
     * query, so the queue can show a fabric badge without an N+1.
     *
     * @param  list<int>  $itemIds
     * @return array<int, string>
     */
    private function fabricStatusMap(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return FabricAllocation::query()
            ->whereIn('order_item_id', $itemIds)
            ->whereIn('status', [FabricAllocation::STATUS_RESERVED, FabricAllocation::STATUS_CONSUMED])
            ->orderBy('id')
            ->get(['order_item_id', 'status'])
            ->keyBy('order_item_id') // ordered ascending → the latest row wins
            ->map(fn (FabricAllocation $a): string => $a->status)
            ->all();
    }

    /**
     * The item's current fabric allocation (reserved first, else consumed).
     *
     * @return array<string, mixed>|null
     */
    private function fabricAllocation(OrderItem $item): ?array
    {
        /** @var FabricAllocation|null $allocation */
        $allocation = FabricAllocation::query()
            ->where('order_item_id', $item->id)
            ->whereIn('status', [FabricAllocation::STATUS_RESERVED, FabricAllocation::STATUS_CONSUMED])
            ->with('roll')
            ->latest('id')
            ->first();

        return $allocation === null ? null : (new FabricAllocationResource($allocation))->resolve();
    }

    /**
     * Cloth damage / waste totals + recent reports for this item, separate from
     * alteration and QC rework. Pending reports have not yet deducted stock.
     *
     * @return array<string, mixed>
     */
    private function clothDamage(OrderItem $item): array
    {
        $reports = DamageReport::query()
            ->where('order_item_id', $item->id)
            ->latest('id')
            ->get();

        $sum = fn ($collection): string => number_format(
            (float) $collection->sum(fn (DamageReport $r): float => (float) $r->quantity_lost_metres),
            2,
            '.',
            '',
        );

        return [
            'count' => $reports->count(),
            'pending_count' => $reports->where('status', DamageReport::STATUS_PENDING)->count(),
            'total_metres' => $sum($reports),
            'approved_metres' => $sum($reports->where('status', DamageReport::STATUS_APPROVED)),
            'recent' => DamageReportListResource::collection($reports->take(5))->resolve(),
        ];
    }

    /**
     * QC pass/fail status + rework context + inspection history for the workbench
     * QC panel (Phase 7C). Internal production rework — never a customer alteration.
     *
     * @return array<string, mixed>
     */
    private function qcSummary(OrderItem $item): array
    {
        $inspections = QcInspection::query()
            ->where('order_item_id', $item->id)
            ->with('inspector:id,name')
            ->orderByDesc('attempt_number')
            ->get();

        /** @var QcInspection|null $latest */
        $latest = $inspections->first();
        $state = (string) $item->state;
        $inRework = $state === OrderItem::STATE_REWORK;

        return [
            'in_qc' => $state === OrderItem::STATE_QC,
            'in_rework' => $inRework,
            'can_inspect' => $state === OrderItem::STATE_QC,
            'attempts' => $inspections->count(),
            'latest' => $latest === null ? null : (new QcInspectionResource($latest))->resolve(),
            'rework' => $inRework && $latest !== null ? [
                'target_stage' => $latest->rework_target_stage,
                'failure_reason' => $latest->failure_reason,
                'failure_stage' => $latest->failure_stage,
                'notes' => $latest->notes,
            ] : null,
            'history' => QcInspectionResource::collection($inspections)->resolve(),
        ];
    }

    /**
     * Final packing status + checklist + ready-rack slot for the workbench
     * (Phase 7D). The rack slot only appears once the item is ready-for-delivery.
     *
     * @return array<string, mixed>
     */
    private function packingSummary(OrderItem $item): array
    {
        $state = (string) $item->state;

        /** @var PackingChecklist|null $checklist */
        $checklist = PackingChecklist::query()->where('order_item_id', $item->id)->first();

        return [
            'state' => $state,
            'is_packing' => $state === OrderItem::STATE_PACKING,
            'is_ready' => $state === OrderItem::STATE_READY_FOR_DELIVERY,
            'is_delivered' => $state === OrderItem::STATE_DELIVERED,
            'can_pack' => $state === OrderItem::STATE_PACKING,
            'checklist' => $checklist === null ? null : (new PackingChecklistResource($checklist))->resolve(),
            'checklist_complete' => $checklist?->isComplete() ?? false,
            'rack_slot' => $this->rackSlot($item),
            'required_checks' => PackingChecklist::REQUIRED_CHECKS,
        ];
    }

    /**
     * The item's active ready-rack slot (Production Box is a separate concept).
     *
     * @return array{slot_code: string, label: string|null}|null
     */
    private function rackSlot(OrderItem $item): ?array
    {
        /** @var RackAssignment|null $assignment */
        $assignment = RackAssignment::query()
            ->where('order_item_id', $item->id)
            ->whereNull('released_at')
            ->first();

        if ($assignment === null) {
            return null;
        }

        /** @var RackSlot|null $slot */
        $slot = RackSlot::query()->find($assignment->rack_slot_id);

        return $slot === null ? null : ['slot_code' => $slot->slot_code, 'label' => $slot->label];
    }
}
