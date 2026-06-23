<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Printing\Models\Document;
use Illuminate\Support\Collection;

/**
 * Read-only Front Desk lookup. Resolves an order from a free-text query (order
 * code, sub-order code, phone last-4, or customer name) and builds a rich,
 * answer-everything summary. Branch isolation is automatic (global BranchScope).
 *
 * Production Box (order_items.box_code) and Ready Rack (RackSlot) are surfaced
 * as distinct fields — never conflated.
 */
final class OrderLookupService
{
    private const LIMIT = 15;

    public function __construct(
        private readonly BalanceService $balances,
        private readonly OrderStatusDeriver $statusDeriver,
        private readonly OrderProgressSummary $progress,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function lookup(string $query): array
    {
        return $this->resolveOrders($query)
            ->map(fn (Order $order): array => $this->orderSummary($order))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rackSearch(string $query): array
    {
        return $this->resolveOrders($query)
            ->map(fn (Order $order): array => $this->rackSummary($order))
            ->all();
    }

    /**
     * @return Collection<int, Order>
     */
    private function resolveOrders(string $query): Collection
    {
        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        $ids = collect();

        // Main order code.
        Order::query()->where('order_code', 'like', "%{$query}%")->pluck('id')->each(fn ($id) => $ids->push($id));

        // Sub-order / item code → parent order.
        OrderItem::query()->where('item_code', 'like', "%{$query}%")->pluck('order_id')->each(fn ($id) => $ids->push($id));

        // Delivery pickup box / shelf number → parent order (exact, for collection).
        OrderItem::query()->where('delivery_box_code', $query)->pluck('order_id')->each(fn ($id) => $ids->push($id));

        // Phone (match the stored last-4 against the query's trailing digits).
        $digits = preg_replace('/\D/', '', $query) ?? '';
        if (strlen($digits) >= 4) {
            $last4 = substr($digits, -4);
            $customerIds = Customer::query()->where('phone_last4', $last4)->pluck('id');
            Order::query()->whereIn('customer_id', $customerIds)->pluck('id')->each(fn ($id) => $ids->push($id));
        }

        // Customer name.
        if (mb_strlen($query) >= 2) {
            $customerIds = Customer::query()->where('name', 'like', "%{$query}%")->pluck('id');
            Order::query()->whereIn('customer_id', $customerIds)->pluck('id')->each(fn ($id) => $ids->push($id));
        }

        $unique = $ids->filter()->unique()->take(self::LIMIT);

        return Order::query()
            ->whereIn('id', $unique)
            ->with(['customer', 'items'])
            ->latest('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function orderSummary(Order $order): array
    {
        $items = $order->items;
        $itemIds = $items->pluck('id');

        $slots = RackSlot::query()->whereIn('current_order_item_id', $itemIds)->get()->keyBy('current_order_item_id');
        $pdfItemIds = Document::query()
            ->where('kind', Document::KIND_JOB_CARD)
            ->where('reference_type', OrderItem::class)
            ->whereIn('reference_id', $itemIds)
            ->pluck('reference_id')->unique()->flip();

        $invoice = Invoice::query()->where('order_id', $order->id)->latest('id')->first();
        $balance = $this->balances->outstandingForOrder($order->id);

        return [
            'id' => $order->id,
            'order_code' => $order->order_code,
            'customer_name' => $order->customer?->name,
            'phone_masked' => $this->maskedPhone($order),
            'lifecycle_status' => $order->lifecycle_status,
            'status' => $this->statusDeriver->derive($items),
            'progress' => $this->progress->summarise($items),
            'order_date' => $order->created_at?->toDateString(),
            'delivery_date' => $order->expected_delivery_date?->toDateString(),
            'is_rush' => $items->contains(fn (OrderItem $i): bool => $this->design($i)['priority'] === 'rush'),
            'invoice' => $invoice === null ? null : [
                'invoice_number' => $invoice->invoice_no,
                'total_amount' => $balance['invoiced_paise'] / 100,
                'paid_amount' => $balance['paid_paise'] / 100,
                'balance_amount' => $balance['outstanding_paise'] / 100,
                'payment_status' => $invoice->status,
            ],
            'items' => $items->map(function (OrderItem $item) use ($slots, $pdfItemIds): array {
                $design = $this->design($item);

                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'product_type' => $item->product_type,
                    'status' => (string) $item->state,
                    'status_label' => OrderProgressSummary::label((string) $item->state),
                    'fabric' => $design['fabric'] ?? $item->fabric_preference_text,
                    'style' => $design['style'],
                    'fit' => $design['fit'],
                    // Production box — used DURING production (separate from ready rack).
                    'box_code' => $item->box_code,
                    'placed_in_box' => (bool) $item->placed_in_box,
                    'pdf_generated' => $pdfItemIds->has($item->id),
                    // Ready rack — pickup location, only once staged for delivery.
                    'ready_rack_slot' => $slots->get($item->id)?->slot_code,
                    // Delivery box — the manually-entered pickup box / shelf number.
                    'delivery_box_code' => $item->delivery_box_code,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rackSummary(Order $order): array
    {
        $items = $order->items;
        $slots = RackSlot::query()->whereIn('current_order_item_id', $items->pluck('id'))->get()->keyBy('current_order_item_id');
        $invoice = Invoice::query()->where('order_id', $order->id)->latest('id')->first();
        $balance = $this->balances->outstandingForOrder($order->id);

        $readyItems = $items->filter(
            fn (OrderItem $i): bool => (string) $i->state === OrderItem::STATE_READY_FOR_DELIVERY,
        );
        // Non-ready, non-cancelled siblings — surfaced so the Ready Rack card can
        // make clear the rest of the order is NOT collectable yet (no extra API call).
        $otherItems = $items->filter(fn (OrderItem $i): bool => !in_array(
            (string) $i->state,
            [OrderItem::STATE_READY_FOR_DELIVERY, OrderItem::STATE_CANCELLED],
            true,
        ));

        return [
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'customer_name' => $order->customer?->name,
            'phone_masked' => $this->maskedPhone($order),
            'delivery_date' => $order->expected_delivery_date?->toDateString(),
            'balance_amount' => $balance['outstanding_paise'] / 100,
            'payment_status' => $invoice?->status,
            'ready' => $slots->isNotEmpty(),
            'current_status' => $this->statusDeriver->derive($items),
            'progress' => $this->progress->summarise($items),
            // Ready rack slots only — production boxes are intentionally NOT here.
            'rack_slots' => $slots->map(fn (RackSlot $s): array => [
                'slot_code' => $s->slot_code,
                'order_item_id' => $s->current_order_item_id,
            ])->values()->all(),
            'ready_sub_orders' => $readyItems->map(fn (OrderItem $i) => [
                'item_code' => $i->item_code,
                'product_type' => $i->product_type,
                'ready_rack_slot' => $slots->get($i->id)?->slot_code,
                'delivery_box_code' => $i->delivery_box_code,
            ])->values()->all(),
            'other_items' => $otherItems->map(fn (OrderItem $i): array => [
                'item_code' => $i->item_code,
                'product_type' => $i->product_type,
                'status' => (string) $i->state,
                'status_label' => OrderProgressSummary::label((string) $i->state),
            ])->values()->all(),
        ];
    }

    private function maskedPhone(Order $order): ?string
    {
        $last4 = $order->customer?->phone_last4;

        return $last4 !== null && $last4 !== '' ? '****' . $last4 : null;
    }

    /**
     * @return array{fabric: mixed, style: mixed, fit: mixed, priority: mixed}
     */
    private function design(OrderItem $item): array
    {
        $d = is_array($item->design_notes) ? $item->design_notes : [];

        return [
            'fabric' => $d['fabric'] ?? null,
            'style' => $d['style'] ?? null,
            'fit' => $d['fit'] ?? null,
            'priority' => $d['priority'] ?? null,
        ];
    }
}
