<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Resources\ProductionItemResource;
use App\Modules\Production\Services\ReworkOverrideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReworkOverrideController extends BaseApiController
{
    public function __construct(private readonly ReworkOverrideService $override) {}

    public function store(Request $request, OrderItem $item): JsonResponse
    {
        $this->authorize('reworkOverride', $item);

        /** @var User $actor */
        $actor = $request->user();
        $notes = $request->filled('notes') ? (string) $request->string('notes') : null;

        $item = $this->override->override($item->id, $notes, $actor);

        return $this->respond((new ProductionItemResource($item))->resolve(), 'Rework override applied');
    }
}
