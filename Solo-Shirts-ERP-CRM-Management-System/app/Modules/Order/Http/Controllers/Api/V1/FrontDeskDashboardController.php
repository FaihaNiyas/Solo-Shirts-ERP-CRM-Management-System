<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Services\FrontDeskDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Branch-scoped, counter-operational summary for the Front Desk dashboard.
 * Gated by the existing orders.view permission (Front Desk already holds it) —
 * it is not a finance report and exposes no revenue/GST/margin data.
 */
final class FrontDeskDashboardController extends BaseApiController
{
    public function __construct(private readonly FrontDeskDashboardService $dashboard) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        abort_unless($actor->can('orders.view'), 403);

        return $this->respond($this->dashboard->summary($actor));
    }
}
