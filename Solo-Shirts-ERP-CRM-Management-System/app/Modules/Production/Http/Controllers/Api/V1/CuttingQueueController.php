<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Resources\CuttingQueueItemResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

final class CuttingQueueController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('viewQueue', OrderItem::class);

        $items = OrderItem::query()
            ->whereIn('state', [
                OrderItem::STATE_DRAFT,
                OrderItem::STATE_FABRIC_ALLOCATED,
                OrderItem::STATE_CUTTING,
            ])
            // Phase 2.5: intake orders are still being prepared at the counter —
            // they must not surface in the cutting queue until final confirm.
            ->whereHas('order', fn (Builder $q): Builder => $q->where('lifecycle_status', '!=', Order::LIFECYCLE_INTAKE))
            ->orderBy('item_code')
            ->get();

        return $this->respond(CuttingQueueItemResource::collection($items)->resolve());
    }
}
