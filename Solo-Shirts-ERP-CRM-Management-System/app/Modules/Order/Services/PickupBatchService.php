<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\PaymentAllocation;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Finance\Services\PaymentAllocationService;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Exceptions\PickupException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\PickupBatch;
use App\Modules\Order\Models\PickupBatchItem;
use App\Modules\Production\Services\StateTransitionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pickup batches — one customer collection event over selected ready shirts
 * (Phase 2). A batch is paid (in full) then handed over; only its shirts are
 * marked delivered and only their rack slots release. The parent order's
 * aggregate status recalculates from the items, so it becomes Partially Delivered
 * after the first batch and Delivered only once every active item is delivered.
 *
 * V1 is pay-now only. There is no path here to hand over an unpaid shirt — that
 * (deferred / credit pickup) is a future, separately-permissioned phase.
 */
final class PickupBatchService
{
    public function __construct(
        private readonly PaymentAllocationService $allocations,
        private readonly PaymentService $payments,
        private readonly BalanceService $balances,
        private readonly PickupNumberService $numbers,
        private readonly StateTransitionService $transitions,
        private readonly OrderProgressSummary $progress,
    ) {}

    /**
     * @param  list<int>  $itemIds
     */
    public function create(Order $order, array $itemIds, string $pickupType, User $actor): PickupBatch
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if ($itemIds === []) {
            throw PickupException::noItems();
        }

        $invoice = $this->invoiceFor($order);
        if ($invoice === null) {
            throw OrderException::noInvoice();
        }

        return DB::transaction(function () use ($order, $itemIds, $pickupType, $actor, $invoice): PickupBatch {
            $items = $order->items()->lockForUpdate()->get()->keyBy('id');

            // Items already held by another open batch (lock to avoid a race).
            $inActive = PickupBatchItem::query()
                ->whereIn('order_item_id', $itemIds)
                ->whereHas('batch', fn ($q) => $q->whereIn('status', PickupBatch::ACTIVE_STATUSES))
                ->lockForUpdate()
                ->pluck('order_item_id')
                ->all();
            $inActive = array_flip($inActive);

            $rows = [];
            $total = 0;
            $paidBefore = 0;
            $due = 0;

            foreach ($itemIds as $id) {
                /** @var OrderItem|null $item */
                $item = $items->get($id);
                if ($item === null) {
                    throw PickupException::itemNotInOrder();
                }

                $state = (string) $item->state;
                if ($state === OrderItem::STATE_DELIVERED) {
                    throw PickupException::itemAlreadyDelivered((string) $item->item_code);
                }
                if ($state !== OrderItem::STATE_READY_FOR_DELIVERY) {
                    throw PickupException::itemNotReady((string) $item->item_code);
                }
                if (isset($inActive[$id])) {
                    throw PickupException::itemInActiveBatch((string) $item->item_code);
                }

                $s = $this->allocations->getItemPaymentSummary($item);
                $total += $s['item_total_paise'];
                $paidBefore += $s['allocated_paid_paise'];
                $due += $s['item_balance_paise'];

                $rows[] = [
                    'order_item_id' => $item->id,
                    'invoice_line_id' => $s['invoice_line_id'],
                    'item_total_paise' => $s['item_total_paise'],
                    'paid_before_paise' => $s['allocated_paid_paise'],
                    'amount_due_paise' => $s['item_balance_paise'],
                    'paid_in_batch_paise' => 0,
                ];
            }

            $order->loadMissing('branch');
            $fy = $this->numbers->fiscalYear();

            $batch = PickupBatch::query()->create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'branch_id' => $order->branch_id,
                'batch_no' => $this->numbers->nextBatchNumber($order->branch, $fy),
                'pickup_type' => $pickupType,
                'payment_mode' => $due === 0 ? PickupBatch::PAYMENT_ALREADY_PAID : PickupBatch::PAYMENT_PAY_NOW,
                'status' => $due === 0 ? PickupBatch::STATUS_PAID : PickupBatch::STATUS_PAYMENT_PENDING,
                'total_paise' => $total,
                'paid_paise' => $paidBefore,
                'balance_paise' => $due,
                'created_by' => $actor->id,
            ]);

            foreach ($rows as $row) {
                $batch->items()->create($row);
            }

            return $batch->load('items');
        });
    }

    /**
     * Collect a payment against the batch and attribute it to the batch's shirts.
     *
     * @return array<string, mixed>
     */
    public function collectPayment(
        PickupBatch $batch,
        int $amountPaise,
        string $method,
        ?string $reference,
        string $idempotencyKey,
        User $actor,
    ): array {
        $this->assertOpen($batch);
        if ($batch->status !== PickupBatch::STATUS_PAYMENT_PENDING) {
            throw PickupException::notPayable();
        }
        if ($amountPaise <= 0) {
            throw PickupException::amountNotPositive();
        }
        if ($amountPaise > $batch->balance_paise) {
            throw PickupException::paymentExceedsBatchBalance();
        }

        $invoice = $this->invoiceFor($batch->order);
        if ($invoice === null) {
            throw OrderException::noInvoice();
        }

        $itemIds = $batch->items->pluck('order_item_id')->map(fn ($v): int => (int) $v)->all();

        DB::transaction(function () use ($batch, $invoice, $amountPaise, $method, $reference, $idempotencyKey, $actor, $itemIds): void {
            $payment = $this->payments->record($invoice, [
                'method' => $method,
                'amount_paise' => $amountPaise,
                'reference_no' => $reference,
            ], $idempotencyKey, $actor);

            $this->allocations->allocatePaymentToSelectedItems(
                $payment,
                $invoice,
                $itemIds,
                $actor,
                $batch->id,
                PaymentAllocation::TYPE_SELECTED_ITEM_BALANCE,
            );

            $this->recomputeBatchMoney($batch);
        });

        return $this->summary($batch->refresh()->load('items'));
    }

    /**
     * Hand over the batch's shirts: transition each to delivered (auto-releasing
     * its rack slot) and stamp a receipt number. Only batch items are touched.
     *
     * @return array<string, mixed>
     */
    public function handover(PickupBatch $batch, User $actor): array
    {
        $this->assertOpen($batch);
        if ($batch->status !== PickupBatch::STATUS_PAID || $batch->balance_paise > 0) {
            throw PickupException::notPaid();
        }

        $batch->loadMissing('items.orderItem', 'order.branch');

        // Every batch item must still be ready and undelivered.
        foreach ($batch->items as $bi) {
            $state = (string) $bi->orderItem->state;
            if ($state === OrderItem::STATE_DELIVERED) {
                throw PickupException::itemAlreadyDelivered((string) $bi->orderItem->item_code);
            }
            if ($state !== OrderItem::STATE_READY_FOR_DELIVERY) {
                throw PickupException::itemNoLongerReady((string) $bi->orderItem->item_code);
            }
        }

        $released = [];

        DB::transaction(function () use ($batch, $actor, &$released): void {
            foreach ($batch->items as $bi) {
                $slot = RackSlot::query()->where('current_order_item_id', $bi->order_item_id)->first();

                $this->transitions->transition(
                    $bi->order_item_id,
                    OrderItem::STATE_DELIVERED,
                    $actor,
                    (string) Str::uuid(),
                    'Picked up (batch ' . $batch->batch_no . ')',
                );

                $bi->forceFill([
                    'delivered_at' => now(),
                    'rack_slot_id' => $slot?->id,
                ])->save();

                if ($slot?->slot_code !== null) {
                    $released[] = $slot->slot_code;
                }
            }

            $fy = $this->numbers->fiscalYear();
            $batch->forceFill([
                'status' => PickupBatch::STATUS_HANDED_OVER,
                'handed_over_at' => now(),
                'handed_over_by' => $actor->id,
                'receipt_no' => $this->numbers->nextReceiptNumber($batch->order->branch, $fy),
            ])->save();
        });

        $order = $batch->order->refresh()->loadMissing('items');

        return [
            'batch_id' => $batch->id,
            'batch_no' => $batch->batch_no,
            'receipt_no' => $batch->receipt_no,
            'status' => $batch->status,
            'delivered_items' => $batch->items->map(fn (PickupBatchItem $bi): string => (string) $bi->orderItem->item_code)->values()->all(),
            'released_rack_slots' => array_values(array_unique($released)),
            'order_progress' => $this->progress->summarise($order->items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(PickupBatch $batch): array
    {
        $batch->loadMissing('items.orderItem');
        $orderBalance = $this->balances->outstandingForOrder($batch->order_id)['outstanding_paise'];

        $itemsStillReady = $batch->items->every(
            fn (PickupBatchItem $bi): bool => (string) $bi->orderItem->state === OrderItem::STATE_READY_FOR_DELIVERY,
        );

        $canPay = $batch->status === PickupBatch::STATUS_PAYMENT_PENDING && $batch->balance_paise > 0;
        $canHandover = $batch->status === PickupBatch::STATUS_PAID && $batch->balance_paise === 0 && $itemsStillReady;

        $blockers = [];
        if ($batch->status === PickupBatch::STATUS_HANDED_OVER) {
            $blockers[] = 'ALREADY_HANDED_OVER';
        } elseif ($batch->status === PickupBatch::STATUS_CANCELLED) {
            $blockers[] = 'CANCELLED';
        } else {
            if ($batch->balance_paise > 0) {
                $blockers[] = 'BALANCE_PENDING';
            }
            if (! $itemsStillReady) {
                $blockers[] = 'ITEM_NOT_READY';
            }
        }

        return [
            'id' => $batch->id,
            'batch_no' => $batch->batch_no,
            'order_id' => $batch->order_id,
            'pickup_type' => $batch->pickup_type,
            'payment_mode' => $batch->payment_mode,
            'status' => $batch->status,
            'total_paise' => $batch->total_paise,
            'paid_paise' => $batch->paid_paise,
            'balance_paise' => $batch->balance_paise,
            'total_amount' => $batch->total_paise / 100,
            'paid_amount' => $batch->paid_paise / 100,
            'balance_amount' => $batch->balance_paise / 100,
            'order_balance_paise' => $orderBalance,
            'order_balance_amount' => $orderBalance / 100,
            'receipt_no' => $batch->receipt_no,
            'can_pay' => $canPay,
            'can_handover' => $canHandover,
            'blockers' => $blockers,
            'items' => $batch->items->map(fn (PickupBatchItem $bi): array => [
                'order_item_id' => $bi->order_item_id,
                'item_code' => (string) $bi->orderItem->item_code,
                'production_state' => (string) $bi->orderItem->state,
                'item_total_paise' => $bi->item_total_paise,
                'paid_before_paise' => $bi->paid_before_paise,
                'amount_due_paise' => $bi->amount_due_paise,
                'paid_in_batch_paise' => $bi->paid_in_batch_paise,
                'delivered_at' => $bi->delivered_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /** Recompute batch money figures from the allocations attributed to it. */
    private function recomputeBatchMoney(PickupBatch $batch): void
    {
        $batch->loadMissing('items');
        $paidInBatchTotal = 0;
        $paidBeforeTotal = 0;

        foreach ($batch->items as $bi) {
            $paidInBatch = (int) PaymentAllocation::query()
                ->where('pickup_batch_id', $batch->id)
                ->where('order_item_id', $bi->order_item_id)
                ->sum('amount_paise');

            $bi->forceFill([
                'paid_in_batch_paise' => $paidInBatch,
                'amount_due_paise' => max(0, $bi->item_total_paise - $bi->paid_before_paise - $paidInBatch),
            ])->save();

            $paidInBatchTotal += $paidInBatch;
            $paidBeforeTotal += $bi->paid_before_paise;
        }

        $paid = $paidBeforeTotal + $paidInBatchTotal;
        $balance = max(0, $batch->total_paise - $paid);

        $batch->forceFill([
            'paid_paise' => $paid,
            'balance_paise' => $balance,
            'status' => $balance === 0 ? PickupBatch::STATUS_PAID : PickupBatch::STATUS_PAYMENT_PENDING,
        ])->save();
    }

    private function assertOpen(PickupBatch $batch): void
    {
        if ($batch->status === PickupBatch::STATUS_CANCELLED) {
            throw PickupException::cancelled();
        }
        if ($batch->status === PickupBatch::STATUS_HANDED_OVER) {
            throw PickupException::alreadyHandedOver();
        }
    }

    private function invoiceFor(Order $order): ?Invoice
    {
        return Invoice::query()->where('order_id', $order->id)->latest('id')->first();
    }
}
