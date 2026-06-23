<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\InspectRequest;
use App\Modules\Production\Http\Resources\QcInspectionResource;
use App\Modules\Production\Models\QcInspection;
use App\Modules\Production\Services\QcInspectionService;
use Illuminate\Http\JsonResponse;

final class QcInspectionController extends BaseApiController
{
    public function __construct(private readonly QcInspectionService $qc) {}

    public function inspect(InspectRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('inspect', $item);

        /** @var User $actor */
        $actor = $request->user();
        $inspection = $this->qc->inspect($item->id, $request->validated(), $actor);

        return $this->respond((new QcInspectionResource($inspection))->resolve(), 'Inspection recorded', 201);
    }

    public function history(OrderItem $item): JsonResponse
    {
        $this->authorize('inspect', $item);

        $inspections = QcInspection::query()
            ->where('order_item_id', $item->id)
            ->with('defects.photos')
            ->orderBy('attempt_number')
            ->get();

        return $this->respond(QcInspectionResource::collection($inspections)->resolve());
    }
}
