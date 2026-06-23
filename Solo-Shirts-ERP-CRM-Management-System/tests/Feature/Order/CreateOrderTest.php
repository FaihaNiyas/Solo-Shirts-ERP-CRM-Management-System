<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);
});

// orderPayload() is a shared helper defined in tests/Pest.php.

it('creates a received order whose items enter production at Fabric Ready', function () {
    $response = $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id))
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'data' => [
                'order_code' => 'SSI-HQ-ORD-000001',
                // A fabric_allocated item still derives a "draft" order status —
                // the order only reads as in_production once cutting starts.
                'status' => 'draft',
            ],
        ]);

    // A received order skips the manual fabric-allocation step: items land on the
    // production floor at "Fabric Ready" (fabric_allocated) immediately.
    expect($response->json('data.items'))->toHaveCount(1)
        ->and($response->json('data.items.0.state'))->toBe('fabric_allocated');

    expect(Order::query()->count())->toBe(1)
        ->and(OrderItem::query()->where('state', 'fabric_allocated')->count())->toBe(1);
});

it('rejects an order with zero items (422 ORDER_REQUIRES_ITEM)', function () {
    $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id, ['items' => []]))
        ->assertStatus(422);
});

it('rejects an order referencing a soft-deleted customer (422 INVALID_CUSTOMER)', function () {
    $this->customer->delete();

    $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id))
        ->assertStatus(422);
});

it('generates gap-free sequential order codes per branch', function () {
    for ($i = 1; $i <= 4; $i++) {
        $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id))
            ->assertCreated();
    }

    $codes = Order::query()->orderBy('id')->pluck('order_code')->all();
    expect($codes)->toBe([
        'SSI-HQ-ORD-000001',
        'SSI-HQ-ORD-000002',
        'SSI-HQ-ORD-000003',
        'SSI-HQ-ORD-000004',
    ])->and(array_unique($codes))->toHaveCount(4);
});
