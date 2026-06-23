<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\HoldItemRequest;
use App\Modules\Production\Http\Resources\ProductionItemResource;
use App\Modules\Production\Services\ProductionHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Put a production item on hold / resume it (Kanban Phase B). "On hold" is an
 * overlay flag — the item keeps its real production state, so the state machine and
 * downstream listeners are untouched.
 */
final class ProductionHoldController extends BaseApiController
{
    public function __construct(private readonly ProductionHoldService $holds) {}

    public function hold(HoldItemRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('holdItem', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();

        $item = $this->holds->hold($item, (string) $request->string('reason'), $actor);

        return $this->respond((new ProductionItemResource($item))->resolve(), 'Item placed on hold');
    }

    public function resume(Request $request, OrderItem $item): JsonResponse
    {
        $this->authorize('holdItem', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();

        $item = $this->holds->resume($item, $actor);

        return $this->respond((new ProductionItemResource($item))->resolve(), 'Item resumed');
    }
}
