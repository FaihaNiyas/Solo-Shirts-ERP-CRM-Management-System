<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only Front Desk lookup: "where is my order?" and ready-rack search. No
 * production transitions, no finance management — just answers.
 */
final class FrontDeskLookupController extends BaseApiController
{
    public function __construct(private readonly OrderLookupService $lookup) {}

    public function orders(Request $request): JsonResponse
    {
        $this->authorize('lookup', Order::class);

        return $this->respond([
            'query' => (string) $request->query('q', ''),
            'results' => $this->lookup->lookup((string) $request->query('q', '')),
        ]);
    }

    public function rack(Request $request): JsonResponse
    {
        $this->authorize('lookup', Order::class);

        return $this->respond([
            'query' => (string) $request->query('q', ''),
            'results' => $this->lookup->rackSearch((string) $request->query('q', '')),
        ]);
    }
}
