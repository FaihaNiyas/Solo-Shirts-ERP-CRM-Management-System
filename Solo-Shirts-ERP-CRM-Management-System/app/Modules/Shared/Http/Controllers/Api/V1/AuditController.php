<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Resources\ProductionTransitionResource;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Shared\Http\Requests\ListActivitiesRequest;
use App\Modules\Shared\Http\Resources\ActivityResource;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

final class AuditController extends BaseApiController
{
    public function activities(ListActivitiesRequest $request): JsonResponse
    {
        $this->authorize('viewActivities', Activity::class);

        $query = Activity::query()->with('causer')->latest('id');

        if ($request->filled('subject_type')) {
            $query->where('subject_type', (string) $request->string('subject_type'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }

        if ($request->filled('actor')) {
            $query->where('causer_id', $request->integer('actor'));
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        return $this->respond(ActivityResource::collection($query->limit(200)->get())->resolve());
    }

    public function transitions(OrderItem $item): JsonResponse
    {
        $this->authorize('viewTransitions', Activity::class);

        $transitions = ProductionTransition::query()
            ->where('order_item_id', $item->id)
            ->orderBy('occurred_at')
            ->get();

        return $this->respond(ProductionTransitionResource::collection($transitions)->resolve());
    }
}
