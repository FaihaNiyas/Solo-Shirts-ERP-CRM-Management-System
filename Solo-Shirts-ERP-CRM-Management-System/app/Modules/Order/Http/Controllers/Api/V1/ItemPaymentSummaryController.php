<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Finance\Services\PaymentAllocationService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Http\JsonResponse;

/**
 * Per-item payment picture for the Front Desk pickup UI (Phase 1). Read-only:
 * exposes the shirt's own balance (its advance share + any selected-item
 * payments) alongside the whole-order balance, and tells the counter whether the
 * shirt can be collected/handed over right now.
 *
 * Item is scoped to the order by route model binding (scopeBindings), so a
 * cross-branch / cross-order item 404s.
 */
final class ItemPaymentSummaryController extends BaseApiController
{
    public function __construct(
        private readonly PaymentAllocationService $allocations,
        private readonly BalanceService $balances,
    ) {}

    public function show(Order $order, OrderItem $item): JsonResponse
    {
        $this->authorize('view', Order::class);

        $summary = $this->allocations->getItemPaymentSummary($item);
        $orderBalance = $this->balances->outstandingForOrder($order->id)['outstanding_paise'];

        $state = (string) $item->state;
        $isReady = $state === OrderItem::STATE_READY_FOR_DELIVERY;
        $isDelivered = $state === OrderItem::STATE_DELIVERED;
        $itemBalance = $summary['item_balance_paise'];

        // V1: a shirt can only be handed over once its own balance is clear. No
        // deferred/credit pickup — that's a future, separately-permissioned phase.
        $blockers = [];
        if ($isDelivered) {
            $blockers[] = 'ITEM_DELIVERED';
        } elseif (! $isReady) {
            $blockers[] = 'ITEM_NOT_READY';
        } elseif ($itemBalance > 0) {
            $blockers[] = 'ITEM_BALANCE_PENDING';
        }

        return $this->respond([
            'order_id' => $order->id,
            ...$summary,
            'order_balance_paise' => $orderBalance,
            'production_state' => $state,
            'is_ready' => $isReady,
            'is_delivered' => $isDelivered,
            'can_collect_item_balance' => ! $isDelivered && $itemBalance > 0,
            'can_handover_item' => $isReady && ! $isDelivered && $itemBalance === 0,
            'blockers' => $blockers,
        ]);
    }
}
