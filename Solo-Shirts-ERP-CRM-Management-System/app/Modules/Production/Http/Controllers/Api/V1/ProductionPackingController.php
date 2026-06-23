<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Delivery\Models\RackAssignment;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Services\DocumentRenderSpec;
use App\Modules\Printing\Services\PdfRenderer;
use App\Modules\Production\Http\Requests\PackingChecklistRequest;
use App\Modules\Production\Http\Resources\PackingChecklistResource;
use App\Modules\Production\Models\PackingChecklist;
use App\Modules\Production\Services\PackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 7D — final packing + ready-rack polish on the production workbench. The
 * checklist and mark-packed are gated by production.packing.manage (Front Desk
 * cannot pack — they still own handover). Marking packed promotes the item to
 * ready-for-delivery, which auto-assigns a rack slot via the existing listener.
 * Packing never marks delivered and never touches the balance (that stays at
 * handover). Branch isolation is the OrderItem global scope.
 */
final class ProductionPackingController extends BaseApiController
{
    public function __construct(
        private readonly PackingService $packing,
        private readonly PdfRenderer $renderer,
    ) {}

    /** Packing status, checklist + rack slot for the item (read). */
    public function show(OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        return $this->respond($this->status($item));
    }

    /** Save/patch the packing checklist (only while in the packing stage). */
    public function saveChecklist(PackingChecklistRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('pack', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();
        $this->packing->saveChecklist($item->id, $request->validated(), $actor);

        return $this->respond($this->status($item->fresh()), 'Packing checklist saved');
    }

    /** Mark packed → promote to ready-for-delivery + auto-assign a rack slot. */
    public function markPacked(Request $request, OrderItem $item): JsonResponse
    {
        $this->authorize('pack', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();
        $item = $this->packing->markPacked($item->id, $actor);

        return $this->respond($this->status($item), 'Item packed and moved to the ready rack');
    }

    /** Per-item packing slip PDF (reuses the shared PDF renderer). */
    public function slip(Request $request, OrderItem $item): JsonResponse
    {
        $this->authorize('view', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();

        $item->loadMissing(['order.customer', 'order.branch', 'measurementVersion.profile']);
        $design = is_array($item->design_notes) ? $item->design_notes : [];

        $spec = new DocumentRenderSpec(
            kind: Document::KIND_PACKING_SLIP,
            referenceType: OrderItem::class,
            referenceId: $item->id,
            branchId: $item->branch_id,
            view: 'pdfs.item_packing_slip',
            data: [
                'order' => $item->order,
                'item' => $item,
                'customer' => $item->order?->customer,
                'version' => $item->measurementVersion,
                'profile' => $item->measurementVersion?->profile?->name,
                'fabric' => $design['fabric'] ?? $item->fabric_preference_text,
                'style' => $design['style'] ?? null,
                'fit' => $design['fit'] ?? null,
                'rackSlot' => $this->rackSlot($item),
                'checklist' => $this->packing->find($item->id),
                'preparedBy' => $actor->name,
            ],
            heavy: false,
        );

        $document = $this->renderer->render($spec, $actor->id);

        return $this->respond([
            ...(new DocumentResource($document))->resolve(),
            'order_item_id' => $item->id,
            'item_code' => $item->item_code,
        ], 'Packing slip ready', 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function status(OrderItem $item): array
    {
        $state = (string) $item->state;
        $checklist = $this->packing->find($item->id);

        return [
            'state' => $state,
            'is_packing' => $state === OrderItem::STATE_PACKING,
            'is_ready' => $state === OrderItem::STATE_READY_FOR_DELIVERY,
            'is_delivered' => $state === OrderItem::STATE_DELIVERED,
            'can_pack' => $state === OrderItem::STATE_PACKING,
            'checklist' => $checklist === null ? null : (new PackingChecklistResource($checklist->loadMissing('packer')))->resolve(),
            'checklist_complete' => $checklist?->isComplete() ?? false,
            'rack_slot' => $this->rackSlot($item),
            'required_checks' => PackingChecklist::REQUIRED_CHECKS,
        ];
    }

    /**
     * The item's active rack slot, or null if not on the rack yet.
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
