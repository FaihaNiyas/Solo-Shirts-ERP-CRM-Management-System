<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\CompleteCuttingRequest;
use App\Modules\Production\Http\Resources\CutBundleResource;
use App\Modules\Production\Http\Resources\ProductionItemResource;
use App\Modules\Production\Services\CuttingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CuttingActionController extends BaseApiController
{
    public function __construct(private readonly CuttingService $cutting) {}

    public function start(Request $request, OrderItem $item): JsonResponse
    {
        $this->authorize('startCutting', $item);

        /** @var User $actor */
        $actor = $request->user();
        $item = $this->cutting->startCutting($item->id, $actor);

        return $this->respond((new ProductionItemResource($item))->resolve(), 'Cutting started');
    }

    public function complete(CompleteCuttingRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('completeCutting', $item);

        /** @var User $actor */
        $actor = $request->user();

        /** @var list<array{pieces: int, notes?: string|null}> $bundles */
        $bundles = $request->validated('bundles');

        $result = $this->cutting->completeCutting(
            $item->id,
            (float) $request->float('actual_metres'),
            $bundles,
            $actor,
        );

        return $this->respond([
            'item' => (new ProductionItemResource($result['item']))->resolve(),
            'bundles' => CutBundleResource::collection($result['bundles'])->resolve(),
        ], 'Cutting completed');
    }
}
