<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\PaymentAllocation;
use App\Modules\Finance\Services\BalanceService;
use App\Modules\Finance\Services\PaymentAllocationService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

/**
 * Create + confirm a multi-shirt order with explicit per-shirt pricing and an
 * advance. Returns [orderId, [itemIds...]].
 *
 * @param  list<int>  $pricesRupees
 * @return array{0:int,1:list<int>}
 */
function allocPricedOrder($ctx, array $pricesRupees, int $advanceRupees): array
{
    $customer = Customer::factory()->for($ctx->branch)->create();
    $version = approvedVersionFor($ctx->branch, $customer);

    $items = array_map(
        fn (): array => ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $version->id],
        $pricesRupees,
    );

    $res = test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customer->id, $version->id, [
            'items' => $items,
            'lifecycle_status' => 'intake_preparation',
        ]))->assertCreated();

    $orderId = $res->json('data.id');
    $itemIds = collect($res->json('data.items'))->pluck('id')->all();

    foreach ($itemIds as $itemId) {
        test()->withHeaders(bearer($ctx->fd))->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
    }

    $lines = [];
    foreach ($itemIds as $i => $itemId) {
        $lines[] = [
            'order_item_id' => $itemId,
            'base_price' => $pricesRupees[$i],
            'style_charge' => 0,
            'rush_charge' => 0,
            'discount_amount' => 0,
            'gst_rate' => 0,
        ];
    }

    $confirm = ['pricing' => ['lines' => $lines]];
    if ($advanceRupees > 0) {
        $confirm['payment'] = ['advance_amount' => $advanceRupees, 'method' => 'cash'];
    }
    test()->withHeaders(bearer($ctx->fd))->postJson("/api/v1/orders/{$orderId}/confirm", $confirm)->assertOk();

    return [$orderId, $itemIds];
}

it('allocates a confirm advance proportionally across invoice lines (paise)', function () {
    // 1500 + 1500 + 2000 = 5000, advance 1000 -> 300 / 300 / 400.
    [$orderId, $itemIds] = allocPricedOrder($this, [1500, 1500, 2000], 1000);

    $byItem = PaymentAllocation::query()
        ->where('order_id', $orderId)
        ->where('allocation_type', PaymentAllocation::TYPE_ADVANCE)
        ->get()
        ->groupBy('order_item_id')
        ->map(fn ($rows) => (int) $rows->sum('amount_paise'));

    expect($byItem[$itemIds[0]])->toBe(30000)
        ->and($byItem[$itemIds[1]])->toBe(30000)
        ->and($byItem[$itemIds[2]])->toBe(40000)
        ->and($byItem->sum())->toBe(100000); // fully allocated = advance
});

it('computes an accurate item balance after the advance', function () {
    [, $itemIds] = allocPricedOrder($this, [1500, 1500, 2000], 1000);

    $summary = app(PaymentAllocationService::class)->getItemPaymentSummary(OrderItem::query()->find($itemIds[0]));

    expect($summary['item_total_paise'])->toBe(150000)
        ->and($summary['allocated_advance_paise'])->toBe(30000)
        ->and($summary['item_balance_paise'])->toBe(120000); // 1500 - 300 = 1200
});

it('keeps order balance equal to the sum of item balances', function () {
    [$orderId] = allocPricedOrder($this, [1500, 1500, 2000], 1000);

    $order = Order::query()->find($orderId);
    $summary = app(PaymentAllocationService::class)->getOrderPaymentSummary($order);
    $orderBalance = app(BalanceService::class)->outstandingForOrder($orderId)['outstanding_paise'];

    expect($summary['items_balance_paise'])->toBe($orderBalance)
        ->and($summary['reconciled'])->toBeTrue()
        ->and($orderBalance)->toBe(400000); // 5000 - 1000 advance
});

it('distributes rounding remainder deterministically to the largest line', function () {
    // 1000 + 1000 + 1000 = 3000, advance 100 (10000 paise) -> 3334/3333/3333.
    [$orderId, $itemIds] = allocPricedOrder($this, [1000, 1000, 1000], 100);

    $byItem = PaymentAllocation::query()
        ->where('order_id', $orderId)
        ->where('allocation_type', PaymentAllocation::TYPE_ADVANCE)
        ->get()->groupBy('order_item_id')->map(fn ($r) => (int) $r->sum('amount_paise'));

    // Equal weights -> remainder paise to the earliest (smallest id) line first.
    expect($byItem->sum())->toBe(10000)
        ->and($byItem[$itemIds[0]])->toBe(3334)
        ->and($byItem[$itemIds[1]])->toBe(3333)
        ->and($byItem[$itemIds[2]])->toBe(3333);
});

it('exposes the per-item payment summary endpoint', function () {
    [$orderId, $itemIds] = allocPricedOrder($this, [1500, 1500, 2000], 1000);

    $this->withHeaders(bearer($this->fd))
        ->getJson("/api/v1/orders/{$orderId}/items/{$itemIds[0]}/payment-summary")
        ->assertOk()
        ->assertJsonPath('data.item_total_paise', 150000)
        ->assertJsonPath('data.allocated_advance_paise', 30000)
        ->assertJsonPath('data.item_balance_paise', 120000)
        ->assertJsonPath('data.can_collect_item_balance', true)
        ->assertJsonPath('data.can_handover_item', false); // not ready yet
});
