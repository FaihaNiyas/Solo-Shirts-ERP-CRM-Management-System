<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\TransitionRequest;
use App\Modules\Production\Http\Resources\ProductionItemResource;
use App\Modules\Production\Services\StateTransitionService;
use Illuminate\Http\JsonResponse;

final class ProductionTransitionController extends BaseApiController
{
    public function __construct(private readonly StateTransitionService $transitions) {}

    public function store(TransitionRequest $request, OrderItem $item): JsonResponse
    {
        $toState = (string) $request->string('to');

        $this->authorize('transition', [$item, $toState]);

        // Production may only act on confirmed orders — intake drafts are off-limits.
        $item->loadMissing('order');
        if ($item->order?->isIntake() === true) {
            throw OrderException::notConfirmedForProduction();
        }
        // A garment with no measurement cannot move through production (cancelling is exempt).
        if ($toState !== OrderItem::STATE_CANCELLED && $item->measurement_version_id === null) {
            throw OrderException::measurementRequiredForProduction();
        }

        /** @var User $actor */
        $actor = $request->user();
        $key = (string) $request->header('Idempotency-Key');
        $notes = $request->filled('notes') ? (string) $request->string('notes') : null;

        $metaInput = $request->input('metadata', []);
        /** @var array<string, mixed> $metadata */
        $metadata = is_array($metaInput) ? $metaInput : [];

        $completedQty = $request->filled('completed_qty') ? (int) $request->integer('completed_qty') : null;
        $rejectedQty = $request->filled('rejected_qty') ? (int) $request->integer('rejected_qty') : null;
        $attachmentPath = $request->filled('attachment_path') ? (string) $request->string('attachment_path') : null;
        $deliveryBoxCode = $request->filled('delivery_box_code') ? trim((string) $request->string('delivery_box_code')) : null;

        $item = $this->transitions->transition(
            $item->id,
            $toState,
            $actor,
            $key,
            $notes,
            $metadata,
            $completedQty,
            $rejectedQty,
            $attachmentPath,
            $deliveryBoxCode,
        );

        return $this->respond((new ProductionItemResource($item))->resolve(), 'Transition applied');
    }
}
