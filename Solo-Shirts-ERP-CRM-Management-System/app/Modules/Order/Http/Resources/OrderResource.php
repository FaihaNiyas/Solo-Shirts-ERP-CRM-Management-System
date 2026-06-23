<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Resources;

use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderProgressSummary;
use App\Modules\Order\Services\OrderStatusDeriver;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * @mixin Order
 */
final class OrderResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            // Full phone only for desk roles who contact the customer; others masked.
            'customer_phone' => $this->whenLoaded('customer', fn () => $this->canSeeFullPhone($request->user()) ? $this->customer?->phone : null),
            'customer_phone_masked' => $this->whenLoaded('customer', fn () => $this->maskPhone($this->customer?->phone)),
            'source' => $this->source,
            // Item-derived production status (draft/in_production/ready/…).
            'status' => app(OrderStatusDeriver::class)->derive($this->items),
            // Lifecycle gate: intake_preparation / order_received / cancelled.
            'lifecycle_status' => $this->lifecycle_status,
            'channel_notes' => $this->channel_notes,
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'delivery_mode' => $this->delivery_mode,
            'delivery_charges_paise' => $this->delivery_charges_paise,
            'notes' => $this->notes,
            // Rupees — invoice total once confirmed, else the intake estimate.
            'total_amount' => $this->resource->computedTotalPaise() / 100,
            'balance_due' => app(BalanceService::class)->outstandingForOrder($this->id)['outstanding_paise'] / 100,
            'paid_amount' => max(0, $this->resource->computedTotalPaise() - app(BalanceService::class)->outstandingForOrder($this->id)['outstanding_paise']) / 100,
            // Derived, display-ready production rollup (partially_ready / counts).
            // Never stored — computed from the already-loaded items (no extra query).
            'progress' => app(OrderProgressSummary::class)->summarise($this->items),
            'items' => $this->itemsWithRackSlots(),
            'created_at' => $this->date($this->created_at),
        ];
    }

    /**
     * Resolve the item resources and merge each one's ready-rack slot. Slots are
     * fetched in a single batched query (keyed by item id) to avoid an N+1.
     *
     * @return array<int, array<string, mixed>>
     */
    private function itemsWithRackSlots(): array
    {
        $items = OrderItemResource::collection($this->items)->resolve();

        $slots = RackSlot::query()
            ->whereIn('current_order_item_id', $this->items->pluck('id'))
            ->pluck('slot_code', 'current_order_item_id');

        return array_map(
            fn (array $item): array => $item + ['ready_rack_slot' => $slots[$item['id']] ?? null],
            $items,
        );
    }

    private function canSeeFullPhone(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Owner', 'Admin', 'Front Desk']);
    }

    private function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        return str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -4);
    }
}
