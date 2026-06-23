<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\OrderProgressSummary;
use Illuminate\Http\JsonResponse;

/**
 * Read-only "order thread" for the production board: every sub-order (OrderItem)
 * of a Main Order with its current production stage, so the board can answer
 * "where is the rest of this customer's order?" without treating the order as a
 * single movable card. Branch isolation is automatic via the model global scopes.
 */
final class ProductionOrderSummaryController extends BaseApiController
{
    public function __construct(private readonly OrderProgressSummary $progress) {}

    public function show(Order $order): JsonResponse
    {
        // Same gate as the rest of the production read surface (production.view).
        $this->authorize('view', OrderItem::class);

        $order->loadMissing(['customer:id,name', 'items:id,order_id,item_code,product_type,state']);

        $due = $order->expected_delivery_date;
        $today = now()->startOfDay();

        $items = $order->items
            ->sortBy('item_code')
            ->values()
            ->map(function (OrderItem $item) use ($due, $today): array {
                $state = (string) $item->state;
                $open = !in_array($state, [OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED], true);

                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'product_type' => $item->product_type,
                    'state' => $state,
                    'state_label' => OrderProgressSummary::label($state),
                    'is_ready' => $state === OrderItem::STATE_READY_FOR_DELIVERY,
                    'is_delivered' => $state === OrderItem::STATE_DELIVERED,
                    'is_cancelled' => $state === OrderItem::STATE_CANCELLED,
                    'is_on_hold' => $item->isOnHold(),
                    'is_overdue' => $open && $due !== null && $due->lt($today),
                ];
            })
            ->all();

        return $this->respond([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'customer_name' => $order->customer?->name,
            'expected_delivery_date' => $due?->toDateString(),
            'progress' => $this->progress->summarise($order->items),
            'items' => $items,
        ]);
    }
}
