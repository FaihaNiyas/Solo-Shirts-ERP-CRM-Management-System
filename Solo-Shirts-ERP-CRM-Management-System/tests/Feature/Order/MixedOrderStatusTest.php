<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Delivery\Models\RackSlot;
use App\Modules\Delivery\Services\RackSlotService;
use App\Modules\Finance\Models\Payment;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Mixed sub-order status across the API: Front Desk lookup + ready-rack search
 * expose the aggregate + non-ready siblings, and a partial pickup hands over
 * only the ready item while the parent rolls up to partially_delivered.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

/**
 * Confirmed order (order_received) with the given item states. Ready items get a
 * rack slot. Returns the Order.
 */
function mixedConfirmedOrder($ctx, array $states, int $totalPaise = 500000, int $paidPaise = 0): Order
{
    $customer = Customer::factory()->for($ctx->branch)->create();
    $order = Order::factory()->for($ctx->branch)->for($customer)->create(['lifecycle_status' => 'order_received']);

    $invoice = makeInvoice($ctx->branch, $order, ['total_paise' => $totalPaise]);
    if ($paidPaise > 0) {
        Payment::factory()->for($ctx->branch)->create(['invoice_id' => $invoice->id, 'amount_paise' => $paidPaise]);
    }

    $i = 0;
    foreach ($states as $state) {
        $item = OrderItem::factory()->for($ctx->branch)->for($order)->create(['state' => $state]);
        if ($state === OrderItem::STATE_READY_FOR_DELIVERY) {
            $code = 'R-A-' . (++$i);
            RackSlot::factory()->for($ctx->branch)->create(['slot_code' => $code, 'is_active' => true, 'current_order_item_id' => null]);
            app(RackSlotService::class)->assign($item->id, $code, null);
        }
    }

    return $order->load('items');
}

it('front-desk lookup returns the aggregate progress and each sub-order status', function () {
    $order = mixedConfirmedOrder($this, ['cutting', 'tailoring', 'ready_for_delivery']);

    $res = $this->withHeaders(bearer($this->fd))
        ->getJson('/api/v1/orders/lookup?q=' . $order->order_code)
        ->assertOk();

    $row = collect($res->json('data.results'))->firstWhere('order_code', $order->order_code);
    expect($row['progress']['aggregate_status'])->toBe('partially_ready')
        ->and($row['progress']['summary_label'])->toBe('Partially Ready — 1 of 3 items ready')
        ->and(collect($row['items'])->pluck('status')->sort()->values()->all())
        ->toBe(['cutting', 'ready_for_delivery', 'tailoring'])
        // Human labels are returned (no raw snake_case leaks through).
        ->and(collect($row['items'])->every(fn (array $it): bool => !str_contains((string) $it['status_label'], '_')))
        ->toBeTrue();
});

it('ready-rack search returns only ready items plus non-ready siblings', function () {
    $order = mixedConfirmedOrder($this, ['cutting', 'tailoring', 'ready_for_delivery']);

    $res = $this->withHeaders(bearer($this->fd))
        ->getJson('/api/v1/rack/search?q=' . $order->order_code)
        ->assertOk();

    $row = collect($res->json('data.results'))->firstWhere('order_code', $order->order_code);

    expect($row['progress']['aggregate_status'])->toBe('partially_ready')
        ->and($row['ready_sub_orders'])->toHaveCount(1)
        ->and($row['other_items'])->toHaveCount(2)
        ->and(collect($row['other_items'])->pluck('status')->sort()->values()->all())->toBe(['cutting', 'tailoring']);
});

it('hands over only the ready item and rolls the parent up to partially_delivered', function () {
    $order = mixedConfirmedOrder($this, ['tailoring', 'ready_for_delivery'], 500000, 500000); // fully paid
    $items = $order->items->keyBy(fn (OrderItem $i): string => (string) $i->state);
    $readyId = $items[OrderItem::STATE_READY_FOR_DELIVERY]->id;
    $tailoringId = $items[OrderItem::STATE_TAILORING]->id;

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$order->id}/handover", ['mode' => 'pickup'])
        ->assertOk();

    // Only the ready item is delivered; the in-production sibling is untouched.
    expect((string) OrderItem::query()->find($readyId)->state)->toBe('delivered')
        ->and((string) OrderItem::query()->find($tailoringId)->state)->toBe('tailoring')
        // Only the delivered item's slot released; sibling never had one.
        ->and(RackSlot::query()->where('current_order_item_id', $readyId)->exists())->toBeFalse();

    // Parent aggregate now partially_delivered.
    $progress = app(\App\Modules\Order\Services\OrderProgressSummary::class)->summarise($order->fresh()->load('items')->items);
    expect($progress['aggregate_status'])->toBe('partially_delivered');
});

it('does not let another branch see a mixed order in lookup', function () {
    $order = mixedConfirmedOrder($this, ['cutting', 'ready_for_delivery']);
    $otherFd = makeUser(makeBranch(['code' => 'BR2']), 'Front Desk');

    $res = $this->withHeaders(bearer($otherFd))
        ->getJson('/api/v1/orders/lookup?q=' . $order->order_code)
        ->assertOk();

    expect(collect($res->json('data.results'))->firstWhere('order_code', $order->order_code))->toBeNull();
});
