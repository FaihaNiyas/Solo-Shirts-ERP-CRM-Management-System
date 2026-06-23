<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Services\StateTransitionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Front Desk pickup handover. Eligibility tells the counter whether an order can
 * be handed over (ready + balance clear); handover marks the ready sub-orders
 * delivered, which auto-releases their ready-rack slots via the existing
 * OrderItemStateChanged listener. This is the counter-pickup path — distinct
 * from the delivery-staff OTP dispatch/confirm flow.
 */
final class HandoverService
{
    public function __construct(
        private readonly BalanceService $balances,
        private readonly StateTransitionService $transitions,
        private readonly OrderStatusDeriver $statusDeriver,
        private readonly OrderProgressSummary $progress,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function eligibility(Order $order, bool $canOverrideBalance): array
    {
        $order->loadMissing('items', 'customer');
        $items = $order->items;
        $slots = RackSlot::query()->whereIn('current_order_item_id', $items->pluck('id'))->get()->keyBy('current_order_item_id');

        $balancePaise = $this->balances->outstandingForOrder($order->id)['outstanding_paise'];
        $invoice = Invoice::query()->where('order_id', $order->id)->latest('id')->first();
        $readyItems = $items->filter(fn (OrderItem $i): bool => (string) $i->state === OrderItem::STATE_READY_FOR_DELIVERY);

        $blockers = [];
        $warnings = [];

        if ($order->lifecycle_status === Order::LIFECYCLE_INTAKE) {
            $blockers[] = 'ORDER_NOT_CONFIRMED';
        }
        if ($order->lifecycle_status === Order::LIFECYCLE_CANCELLED) {
            $blockers[] = 'ORDER_CANCELLED';
        }
        if ($readyItems->isEmpty()) {
            $blockers[] = 'ORDER_NOT_READY';
        } elseif ($readyItems->contains(fn (OrderItem $i): bool => !$slots->has($i->id))) {
            $blockers[] = 'NO_READY_RACK_SLOT';
        }
        if ($balancePaise > 0) {
            $canOverrideBalance ? ($warnings[] = 'BALANCE_PENDING') : ($blockers[] = 'BALANCE_PENDING');
        }

        return [
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'customer_name' => $order->customer?->name,
            'lifecycle_status' => $order->lifecycle_status,
            'delivery_mode' => $order->delivery_mode,
            'ready' => $readyItems->isNotEmpty(),
            'progress' => $this->progress->summarise($items),
            'ready_count' => $readyItems->count(),
            'not_ready_count' => $items->reject(
                fn (OrderItem $i): bool => in_array((string) $i->state, [OrderItem::STATE_READY_FOR_DELIVERY, OrderItem::STATE_DELIVERED, OrderItem::STATE_CANCELLED], true),
            )->count(),
            'balance_amount' => $balancePaise / 100,
            'payment_status' => $invoice?->status,
            'can_handover' => $blockers === [],
            'blockers' => array_values(array_unique($blockers)),
            'warnings' => array_values(array_unique($warnings)),
            'rack_slots' => $readyItems
                ->map(fn (OrderItem $i): ?string => $slots->get($i->id)?->slot_code)
                ->filter()->values()->all(),
            'sub_orders' => $items->map(fn (OrderItem $i): array => [
                'item_code' => $i->item_code,
                'status' => (string) $i->state,
                'ready' => (string) $i->state === OrderItem::STATE_READY_FOR_DELIVERY,
                'rack_slot' => $slots->get($i->id)?->slot_code,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function handover(Order $order, User $actor, string $mode, ?string $notes, bool $canOverrideBalance): array
    {
        if ($mode !== 'pickup') {
            throw OrderException::handoverModeUnsupported();
        }
        if ($order->lifecycle_status === Order::LIFECYCLE_INTAKE) {
            throw OrderException::notConfirmedForProduction();
        }
        if ($order->lifecycle_status === Order::LIFECYCLE_CANCELLED) {
            throw OrderException::cannotConfirmCancelled();
        }

        $readyItems = $order->items()->get()
            ->filter(fn (OrderItem $i): bool => (string) $i->state === OrderItem::STATE_READY_FOR_DELIVERY)
            ->values();

        if ($readyItems->isEmpty()) {
            throw OrderException::orderNotReady();
        }

        $balancePaise = $this->balances->outstandingForOrder($order->id)['outstanding_paise'];
        if ($balancePaise > 0 && !$canOverrideBalance) {
            throw OrderException::balancePending($balancePaise);
        }

        $released = [];

        DB::transaction(function () use ($readyItems, $actor, $notes, &$released): void {
            foreach ($readyItems as $item) {
                // Capture the slot before the delivered-transition listener frees it.
                $slotCode = RackSlot::query()->where('current_order_item_id', $item->id)->value('slot_code');

                $this->transitions->transition(
                    $item->id,
                    OrderItem::STATE_DELIVERED,
                    $actor,
                    (string) Str::uuid(),
                    $notes ?? 'Picked up at front desk',
                );

                if ($slotCode !== null) {
                    $released[] = $slotCode;
                }
            }
        });

        $order->refresh()->loadMissing('items');

        return [
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'status' => $this->statusDeriver->derive($order->items),
            'delivered_sub_orders' => $readyItems->map(fn (OrderItem $i): string => $i->item_code)->values()->all(),
            'released_rack_slots' => array_values(array_unique($released)),
        ];
    }
}
