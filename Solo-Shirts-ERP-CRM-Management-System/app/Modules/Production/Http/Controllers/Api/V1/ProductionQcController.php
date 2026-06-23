<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Http\Resources\DamageReportListResource;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\QcFailRequest;
use App\Modules\Production\Http\Requests\QcPassRequest;
use App\Modules\Production\Http\Resources\QcInspectionResource;
use App\Modules\Production\Models\QcInspection;
use App\Modules\Production\Services\QcInspectionService;
use Illuminate\Http\JsonResponse;

/**
 * Phase 7C — Production QC pass/fail + rework closure on the workbench. A thin,
 * shop-floor-friendly layer over the Phase 10 QcInspectionService: pass moves the
 * item to packing, fail parks it in internal rework with a reason + target stage.
 * This is internal production rework before delivery — it is NOT a customer
 * alteration (those live in alteration_requests and never touch order_item.state).
 * Branch isolation is the OrderItem global scope; Front Desk lacks qc.inspect so
 * the action endpoints 403 for them.
 */
final class ProductionQcController extends BaseApiController
{
    public function __construct(private readonly QcInspectionService $qc) {}

    /** QC status, latest result, rework context + history for the item. */
    public function show(OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        $inspections = QcInspection::query()
            ->where('order_item_id', $item->id)
            ->with('inspector:id,name')
            ->orderByDesc('attempt_number')
            ->get();

        /** @var QcInspection|null $latest */
        $latest = $inspections->first();
        $state = (string) $item->state;
        $inRework = $state === OrderItem::STATE_REWORK;

        // Surface existing fabric-damage reports for the item when the failure is
        // damage-related, so QC can cross-reference them (read-only — Phase 7B owns
        // the damage workflow; this never changes it).
        $relatedDamage = ($inRework && $latest?->failure_reason === 'fabric_damage')
            ? DamageReport::query()->where('order_item_id', $item->id)->latest('id')->get()
            : collect();

        return $this->respond([
            'state' => $state,
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
            'related_damage' => DamageReportListResource::collection($relatedDamage)->resolve(),
        ]);
    }

    /** Pass QC → item moves to packing. */
    public function pass(QcPassRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('inspect', $item);

        /** @var User $actor */
        $actor = $request->user();

        $inspection = $this->qc->inspect($item->id, [
            'disposition' => QcInspection::DISPOSITION_PASS,
            'notes' => $request->filled('notes') ? (string) $request->string('notes') : null,
        ], $actor);

        return $this->respond((new QcInspectionResource($inspection))->resolve(), 'QC passed', 201);
    }

    /** Fail QC → item parks in internal rework, routed to the target stage. */
    public function fail(QcFailRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('inspect', $item);

        /** @var User $actor */
        $actor = $request->user();

        $inspection = $this->qc->inspect($item->id, [
            'disposition' => QcInspection::DISPOSITION_REWORK,
            'failure_reason' => $request->string('failure_reason')->value(),
            'failure_stage' => $request->filled('failure_stage') ? (string) $request->string('failure_stage') : null,
            'rework_target_stage' => $request->string('rework_target_stage')->value(),
            'notes' => $request->filled('notes') ? (string) $request->string('notes') : null,
        ], $actor);

        return $this->respond((new QcInspectionResource($inspection))->resolve(), 'QC failed — sent to rework', 201);
    }
}
