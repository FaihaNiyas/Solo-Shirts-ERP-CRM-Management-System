<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Http\Requests\AddItemRequest;
use App\Modules\Order\Http\Requests\UpdateItemRequest;
use App\Modules\Order\Http\Resources\OrderItemResource;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderItemController extends BaseApiController
{
    public function __construct(private readonly OrderService $orders) {}

    public function store(AddItemRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', Order::class);

        /** @var User $actor */
        $actor = $request->user();
        $item = $this->orders->addItem($order, $request->validated(), $actor);

        return $this->respond((new OrderItemResource($item))->resolve(), 'Item added', 201);
    }

    public function update(UpdateItemRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        $this->authorize('update', Order::class);

        $item = $this->orders->updateItem($item, $request->validated());

        return $this->respond((new OrderItemResource($item))->resolve(), 'Item updated');
    }

    public function destroy(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        $this->authorize('cancel', Order::class);

        $reason = $request->filled('reason') ? (string) $request->string('reason') : null;
        $item = $this->orders->cancelItem($item, $reason);

        return $this->respond((new OrderItemResource($item))->resolve(), 'Item cancelled');
    }
}
