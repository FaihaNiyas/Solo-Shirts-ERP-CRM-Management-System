<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Exceptions\OrderException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Printing\Models\Document;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly OrderCodeGenerator $codes,
        private readonly BoxAssignmentService $boxes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOrder(array $data, User $actor): Order
    {
        $customer = Customer::query()->find($data['customer_id']);

        if ($customer === null) {
            throw OrderException::invalidCustomer();
        }

        /** @var list<array<string, mixed>> $items */
        $items = $data['items'] ?? [];

        if ($items === []) {
            throw OrderException::requiresItem();
        }

        return DB::transaction(function () use ($data, $actor, $customer, $items): Order {
            $branch = Branch::query()->findOrFail($customer->branch_id);
            $code = $this->codes->next($branch);

            $order = Order::query()->create([
                'branch_id' => $customer->branch_id,
                'order_code' => $code,
                'customer_id' => $customer->id,
                'source' => $data['source'],
                'lifecycle_status' => $data['lifecycle_status'] ?? Order::LIFECYCLE_ORDER_RECEIVED,
                'channel_notes' => $data['channel_notes'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'delivery_mode' => $data['delivery_mode'],
                'delivery_charges_paise' => $data['delivery_charges_paise'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($items as $index => $item) {
                $this->makeItem($order, $item, $index + 1);
            }

            return $order->load('items', 'customer');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addItem(Order $order, array $data, User $actor): OrderItem
    {
        $order->forceFill(['updated_by' => $actor->id])->save();
        $next = (int) $order->items()->count() + 1;

        return $this->makeItem($order, $data, $next);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(OrderItem $item, array $data): OrderItem
    {
        if (!$item->isEditable()) {
            throw OrderException::invalidStateForEdit();
        }

        $item->fill([
            'product_type' => $data['product_type'] ?? $item->product_type,
            'quantity' => $data['quantity'] ?? $item->quantity,
            'fabric_preference_text' => $data['fabric_preference_text'] ?? $item->fabric_preference_text,
            'design_notes' => $data['design_notes'] ?? $item->design_notes,
        ])->save();

        return $item;
    }

    public function cancelItem(OrderItem $item, ?string $reason): OrderItem
    {
        if (!$item->isCancellable()) {
            throw OrderException::invalidStateForCancel();
        }

        $item->update([
            'state' => OrderItem::STATE_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        return $item;
    }

    public function cancelOrder(Order $order, ?string $reason): Order
    {
        return DB::transaction(function () use ($order, $reason): Order {
            $items = $order->items()->get();

            foreach ($items as $item) {
                if ((string) $item->state !== OrderItem::STATE_CANCELLED && !$item->isCancellable()) {
                    throw OrderException::invalidStateForCancel();
                }
            }

            foreach ($items as $item) {
                if ((string) $item->state !== OrderItem::STATE_CANCELLED) {
                    // Free the production box so it returns to the pool.
                    $this->boxes->releaseCurrent($item);
                    $item->update([
                        'state' => OrderItem::STATE_CANCELLED,
                        'cancelled_at' => now(),
                        'cancel_reason' => $reason,
                    ]);
                }
            }

            $order->forceFill(['lifecycle_status' => Order::LIFECYCLE_CANCELLED])->save();

            return $order->load('items');
        });
    }

    /**
     * Promote an intake order to "Order Received", releasing it to production.
     * Idempotent: a re-confirm of an already-received order is a no-op. Requires
     * every active sub-order to carry a production box and a generated job-card.
     */
    public function confirmOrder(Order $order, User $actor): Order
    {
        if ($order->lifecycle_status === Order::LIFECYCLE_CANCELLED) {
            throw OrderException::cannotConfirmCancelled();
        }

        if ($order->lifecycle_status === Order::LIFECYCLE_ORDER_RECEIVED) {
            return $order->load('items', 'customer');
        }

        return DB::transaction(function () use ($order, $actor): Order {
            $items = $order->items()->get()
                ->reject(fn (OrderItem $item): bool => (string) $item->state === OrderItem::STATE_CANCELLED);

            if ($items->isEmpty()) {
                throw OrderException::requiresItem();
            }

            foreach ($items as $item) {
                // Production boxes are no longer part of the workflow; a sub-order
                // only needs its job-card PDF generated to be confirmable.
                $hasPdf = Document::query()
                    ->where('kind', Document::KIND_JOB_CARD)
                    ->where('reference_type', OrderItem::class)
                    ->where('reference_id', $item->id)
                    ->exists();

                if (!$hasPdf) {
                    throw OrderException::confirmMissingPdf($item->item_code);
                }
            }

            // Confirming releases the order to the production floor: every active
            // draft item enters production at "Fabric Ready" with no manual fabric-
            // allocation step. Items already advanced keep their current stage.
            foreach ($items as $item) {
                if ((string) $item->state === OrderItem::STATE_DRAFT) {
                    $item->update(['state' => OrderItem::STATE_FABRIC_ALLOCATED]);
                }
            }

            $order->forceFill([
                'lifecycle_status' => Order::LIFECYCLE_ORDER_RECEIVED,
                'updated_by' => $actor->id,
            ])->save();

            return $order->load('items', 'customer');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOrder(Order $order, array $data, User $actor): Order
    {
        $order->fill([
            'source' => $data['source'] ?? $order->source,
            'channel_notes' => $data['channel_notes'] ?? $order->channel_notes,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? $order->expected_delivery_date,
            'delivery_mode' => $data['delivery_mode'] ?? $order->delivery_mode,
            'delivery_charges_paise' => $data['delivery_charges_paise'] ?? $order->delivery_charges_paise,
            'notes' => $data['notes'] ?? $order->notes,
            'updated_by' => $actor->id,
        ])->save();

        return $order->load('items', 'customer');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function makeItem(Order $order, array $item, int $sequence): OrderItem
    {
        return $order->items()->create([
            'branch_id' => $order->branch_id,
            'item_code' => $order->order_code . '-' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'product_type' => $item['product_type'],
            'quantity' => $item['quantity'] ?? 1,
            'measurement_version_id' => $item['measurement_version_id'],
            'fabric_preference_text' => $item['fabric_preference_text'] ?? null,
            'design_notes' => $item['design_notes'] ?? null,
            'state' => $this->entryStateFor($order),
        ]);
    }

    /**
     * Where a freshly-created item enters the workflow. A received order goes
     * straight onto the production floor at "Fabric Ready" — no separate fabric-
     * allocation step. An intake order (still being prepared at the Front Desk)
     * keeps its items in draft (editable) until it is confirmed; confirmOrder()
     * then releases them to Fabric Ready.
     */
    private function entryStateFor(Order $order): string
    {
        return $order->lifecycle_status === Order::LIFECYCLE_ORDER_RECEIVED
            ? OrderItem::STATE_FABRIC_ALLOCATED
            : OrderItem::STATE_DRAFT;
    }
}
