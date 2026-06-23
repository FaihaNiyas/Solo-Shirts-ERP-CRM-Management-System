<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
    $this->customer = Customer::factory()->for($this->branch)->create();
    $this->version = approvedVersionFor($this->branch, $this->customer);
});

it('returns the same order for a repeated Idempotency-Key + body', function () {
    $key = 'order-key-001';
    $payload = orderPayload($this->customer->id, $this->version->id);

    $first = $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/orders', $payload)->assertCreated();

    $second = $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/orders', $payload)->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'));
    expect(Order::query()->count())->toBe(1);
});

it('returns 409 IDEMPOTENCY_CONFLICT for the same key but a different body', function () {
    $key = 'order-key-002';

    $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id))
        ->assertCreated();

    $this->withHeaders(bearer($this->user) + ['Idempotency-Key' => $key])
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id, ['source' => 'phone']))
        ->assertStatus(409)
        ->assertJson(['code' => 'IDEMPOTENCY_CONFLICT']);
});

it('requires an Idempotency-Key header', function () {
    $this->withHeaders(bearer($this->user))
        ->postJson('/api/v1/orders', orderPayload($this->customer->id, $this->version->id))
        ->assertStatus(400)
        ->assertJson(['code' => 'IDEMPOTENCY_KEY_REQUIRED']);
});
