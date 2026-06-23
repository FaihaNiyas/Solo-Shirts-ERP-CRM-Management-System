<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

/**
 * One Main Order with several sub-orders, each sitting in a different production
 * stage — the core "one order, many independent items" scenario.
 *
 * @return array{order: Order, items: array<string, OrderItem>}
 */
function orderWithItems(): array
{
    $branch = test()->branch;
    $customer = Customer::factory()->for($branch)->create(['name' => 'Amal Vazquez']);
    $order = Order::factory()->for($branch)->for($customer)->create([
        'expected_delivery_date' => now()->addDays(5)->toDateString(),
    ]);

    $items = [];
    foreach (['cutting', 'qc', 'ready_for_delivery'] as $i => $state) {
        $items[$state] = OrderItem::factory()->for($branch)->for($order)->create([
            'state' => $state,
            'item_code' => sprintf('SSI-HQ-ITEM-%04d', $i + 1),
        ]);
    }

    return ['order' => $order, 'items' => $items];
}

/** Find a board item by id across all columns. */
function findBoardItem(array $board, int $id): ?array
{
    foreach ($board['columns'] as $col) {
        foreach ($col['items'] as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
    }

    return null;
}

it('shows 5 items as 5 independent cards, each in its own stage', function () {
    $branch = $this->branch;
    $customer = Customer::factory()->for($branch)->create();
    $order = Order::factory()->for($branch)->for($customer)->create();

    $states = ['cutting', 'tailoring', 'finishing', 'qc', 'packing'];
    $created = [];
    foreach ($states as $i => $state) {
        $created[$state] = OrderItem::factory()->for($branch)->for($order)->create([
            'state' => $state,
            'item_code' => sprintf('SSI-HQ-ITEM-%04d', $i + 1),
        ]);
    }

    $board = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board')
        ->assertOk()
        ->json('data');

    // Each item appears once, in the column matching its own state.
    foreach ($states as $state) {
        $card = findBoardItem($board, $created[$state]->id);
        expect($card)->not->toBeNull();
        expect($card['state'])->toBe($state);
    }
});

it('reports each card sibling index and count', function () {
    ['items' => $items] = orderWithItems();

    $board = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board')
        ->assertOk()
        ->json('data');

    $indices = [];
    foreach ($items as $item) {
        $card = findBoardItem($board, $item->id);
        expect($card['sibling_count'])->toBe(3);
        $indices[] = $card['sibling_index'];
    }

    // 1,2,3 in some order — unique positions covering the whole order.
    sort($indices);
    expect($indices)->toBe([1, 2, 3]);
});

it('derives the parent order progress from its item states', function () {
    ['order' => $order] = orderWithItems();

    $data = $this->withHeaders(bearer($this->user))
        ->getJson("/api/v1/production/orders/{$order->id}/summary")
        ->assertOk()
        ->json('data');

    expect($data['items'])->toHaveCount(3);
    expect($data['customer_name'])->toBe('Amal Vazquez');

    // 1 of 3 ready, none delivered → Partially Ready.
    expect($data['progress']['aggregate_status'])->toBe('partially_ready');
    expect($data['progress']['progress']['ready'])->toBe(1);
    expect($data['progress']['progress']['total'])->toBe(3);

    // Every sibling carries a human-readable stage label.
    $labels = collect($data['items'])->pluck('state_label')->all();
    expect($labels)->toContain('Cutting', 'QC', 'Ready for Pickup');
});

it('keeps the order summary branch-isolated', function () {
    ['order' => $order] = orderWithItems();

    $otherBranch = makeBranch(['code' => 'BR2']);
    $intruder = makeUser($otherBranch, 'Production Supervisor');

    // A supervisor from another branch cannot read this order's thread.
    $this->withHeaders(bearer($intruder))
        ->getJson("/api/v1/production/orders/{$order->id}/summary")
        ->assertNotFound();
});
