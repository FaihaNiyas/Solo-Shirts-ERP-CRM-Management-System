<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Entry-to-production flow. A received order puts its items straight onto the
 * production floor at "Fabric Ready" (fabric_allocated) with no manual fabric-
 * allocation step; an intake order keeps its items in draft until it is confirmed,
 * and confirming releases them to Fabric Ready. The board-level tests prove the
 * card actually appears in the Fabric Ready section of the kanban — not just that
 * the stored state changed.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);
});

/** @return array<int, array<string, mixed>> */
function itemsPayload($ctx, int $count): array
{
    $items = [];
    for ($i = 0; $i < $count; $i++) {
        $items[] = ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $ctx->version->id];
    }

    return $items;
}

/** POST a new order and return its id. */
function createOrder($ctx, int $count, ?string $lifecycle = null): int
{
    $overrides = ['items' => itemsPayload($ctx, $count)];
    if ($lifecycle !== null) {
        $overrides['lifecycle_status'] = $lifecycle;
    }

    return test()->withHeaders(bearer($ctx->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($ctx->customer->id, $ctx->version->id, $overrides))
        ->assertCreated()
        ->json('data.id');
}

/** Make every sub-order confirm-ready (box + job-card PDF). */
function makeConfirmReady($ctx, int $orderId): void
{
    foreach (OrderItem::query()->where('order_id', $orderId)->pluck('id') as $itemId) {
        test()->withHeaders(bearer($ctx->fd))
            ->postJson("/api/v1/orders/{$orderId}/items/{$itemId}/box", ['mode' => 'auto'])->assertOk();
        test()->withHeaders(bearer($ctx->fd))
            ->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
    }
}

/** The item_codes the production board shows under a given state column. */
function boardItemCodes($ctx, string $state): array
{
    $res = test()->withHeaders(bearer($ctx->supervisor))
        ->getJson('/api/v1/production/board')->assertOk();

    $column = collect($res->json('data.columns'))->firstWhere('state', $state) ?? ['items' => []];

    return collect($column['items'] ?? [])->pluck('item_code')->all();
}

/** The distinct states of an order's items. */
function orderItemStates(int $orderId): array
{
    return OrderItem::query()->where('order_id', $orderId)->pluck('state')
        ->map(fn ($s): string => (string) $s)->unique()->values()->all();
}

// --- State-level guarantees --------------------------------------------------

it('places items straight into Fabric Ready when an order is created as received', function () {
    $orderId = createOrder($this, 2);

    expect(orderItemStates($orderId))->toBe([OrderItem::STATE_FABRIC_ALLOCATED]);
});

it('keeps items in draft while an order is still an intake', function () {
    $orderId = createOrder($this, 2, 'intake_preparation');

    expect(orderItemStates($orderId))->toBe([OrderItem::STATE_DRAFT]);
});

it('releases draft items to Fabric Ready when an intake order is confirmed', function () {
    $orderId = createOrder($this, 2, 'intake_preparation');
    makeConfirmReady($this, $orderId);

    expect(orderItemStates($orderId))->toBe([OrderItem::STATE_DRAFT]); // still draft pre-confirm

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm", ['pricing' => ['total_amount' => 4000]])
        ->assertOk()
        ->assertJsonPath('data.order.lifecycle_status', 'order_received');

    expect(orderItemStates($orderId))->toBe([OrderItem::STATE_FABRIC_ALLOCATED]);
});

// --- Board-level guarantees (the card really shows in the section) -----------

it('shows a received order\'s cards in the Fabric Ready section of the board', function () {
    $orderId = createOrder($this, 2);

    $codes = OrderItem::query()->where('order_id', $orderId)->pluck('item_code')->all();

    expect(boardItemCodes($this, OrderItem::STATE_FABRIC_ALLOCATED))
        ->toEqualCanonicalizing($codes)
        ->and(boardItemCodes($this, OrderItem::STATE_DRAFT))->toBe([]);
});

it('moves the card into Fabric Ready on the board only once the order is confirmed', function () {
    $orderId = createOrder($this, 1, 'intake_preparation');
    makeConfirmReady($this, $orderId);

    $code = OrderItem::query()->where('order_id', $orderId)->value('item_code');

    // An intake order is not production work yet — it appears in NO board column.
    expect(boardItemCodes($this, OrderItem::STATE_FABRIC_ALLOCATED))->not->toContain($code)
        ->and(boardItemCodes($this, OrderItem::STATE_DRAFT))->not->toContain($code);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/orders/{$orderId}/confirm", ['pricing' => ['total_amount' => 2000]])
        ->assertOk();

    // After confirm the very same card is sitting in the Fabric Ready section.
    expect(boardItemCodes($this, OrderItem::STATE_FABRIC_ALLOCATED))->toContain($code);
});
