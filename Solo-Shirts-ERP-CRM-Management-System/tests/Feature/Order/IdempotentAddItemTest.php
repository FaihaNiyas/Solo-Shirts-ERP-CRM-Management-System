<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Adding an order item is idempotent (QA-002): a double-submit with the same
 * Idempotency-Key replays the first item rather than minting a duplicate line,
 * and a same-key/different-body retry is rejected with IDEMPOTENCY_CONFLICT.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->frontDesk = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);

    // A draft order with one item, built directly so beforeEach does not set a
    // persistent Idempotency-Key header that would leak into the keyless test.
    $order = Order::factory()->for($this->branch)->for($this->customer)->create();
    OrderItem::factory()->for($this->branch)->for($order)->create([
        'state' => OrderItem::STATE_DRAFT,
        'measurement_version_id' => $this->version->id,
    ]);
    $this->orderId = $order->id;
});

/** @return array<string, mixed> */
function addItemPayload(int $versionId, array $overrides = []): array
{
    return array_merge([
        'product_type' => 'pant',
        'quantity' => 1,
        'measurement_version_id' => $versionId,
    ], $overrides);
}

it('replays the same item for a repeated key and conflicts on a different body', function () {
    $key = (string) Str::uuid();

    $first = $this->withHeaders(bearer($this->frontDesk) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/orders/{$this->orderId}/items", addItemPayload($this->version->id))
        ->assertCreated()
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');

    $this->withHeaders(bearer($this->frontDesk) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/orders/{$this->orderId}/items", addItemPayload($this->version->id))
        ->assertCreated()
        ->assertJsonPath('data.id', $first->json('data.id'));

    $this->withHeaders(bearer($this->frontDesk) + ['Idempotency-Key' => $key])
        ->postJson("/api/v1/orders/{$this->orderId}/items", addItemPayload($this->version->id, ['product_type' => 'shirt']))
        ->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

    // The starting order had 1 item; exactly one more was added.
    expect(OrderItem::query()->where('order_id', $this->orderId)->count())->toBe(2);
});

it('rejects adding an item with no Idempotency-Key (400)', function () {
    $this->withHeaders(bearer($this->frontDesk))
        ->postJson("/api/v1/orders/{$this->orderId}/items", addItemPayload($this->version->id))
        ->assertStatus(400)
        ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED')
        ->assertJsonPath('request_id', fn ($id) => is_string($id) && $id !== '');
});
