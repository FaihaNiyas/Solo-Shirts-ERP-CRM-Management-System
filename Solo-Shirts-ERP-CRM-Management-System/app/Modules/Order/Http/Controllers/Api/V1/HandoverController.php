<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Http\Requests\HandoverRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\HandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Front Desk pickup handover with a balance gate. Eligibility is read-only;
 * handover marks ready sub-orders delivered and releases their rack slots.
 */
final class HandoverController extends BaseApiController
{
    public function __construct(private readonly HandoverService $handover) {}

    public function eligibility(Request $request, Order $order): JsonResponse
    {
        $this->authorize('handover', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        return $this->respond(
            $this->handover->eligibility($order, $actor->can('orders.handover.override_balance')),
        );
    }

    public function store(HandoverRequest $request, Order $order): JsonResponse
    {
        $this->authorize('handover', Order::class);

        /** @var User $actor */
        $actor = $request->user();

        $result = $this->handover->handover(
            $order,
            $actor,
            (string) $request->input('mode', 'pickup'),
            $request->input('notes'),
            $actor->can('orders.handover.override_balance'),
        );

        return $this->respond($result, 'Order handed over');
    }
}
