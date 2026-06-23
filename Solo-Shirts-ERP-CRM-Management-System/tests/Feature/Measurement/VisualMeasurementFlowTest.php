<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->fd = makeUser($this->branch, 'Front Desk');
});

function pendingVersionFor($branch, Customer $customer): MeasurementVersion
{
    $profile = MeasurementProfile::factory()->for($branch)->for($customer)->create();

    return MeasurementVersion::factory()->pending()->for($branch)->for($profile, 'profile')->create();
}

it('lets Front Desk create a shirt measurement without approval', function () {
    $customer = Customer::factory()->for($this->branch)->create();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/customers/{$customer->id}/measurements", [
            'name' => 'Regular Fit',
            'type' => 'shirt',
            'shirt_data' => ['chest' => 40, 'shoulder' => 18, 'shirt_length' => 30, 'sleeve_length' => 24],
        ])->assertCreated();

    expect(MeasurementProfile::query()->where('customer_id', $customer->id)->where('type', 'shirt')->exists())->toBeTrue();
});

it('lets Front Desk create a trouser measurement without approval', function () {
    $customer = Customer::factory()->for($this->branch)->create();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/customers/{$customer->id}/measurements", [
            'name' => 'Slim Fit',
            'type' => 'pant',
            'pant_data' => ['waist' => 32, 'length' => 40, 'in_seam' => 30, 'thigh' => 22],
        ])->assertCreated();

    expect(MeasurementProfile::query()->where('customer_id', $customer->id)->where('type', 'pant')->exists())->toBeTrue();
});

it('validates shirt measurement field ranges', function () {
    $customer = Customer::factory()->for($this->branch)->create();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/customers/{$customer->id}/measurements", [
            'name' => 'Bad', 'type' => 'shirt', 'shirt_data' => ['chest' => 999],
        ])->assertStatus(422)->assertJsonValidationErrors(['shirt_data.chest']);
});

it('validates trouser measurement field ranges', function () {
    $customer = Customer::factory()->for($this->branch)->create();

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/customers/{$customer->id}/measurements", [
            'name' => 'Bad', 'type' => 'pant', 'pant_data' => ['waist' => -5],
        ])->assertStatus(422)->assertJsonValidationErrors(['pant_data.waist']);
});

it('lets different sub-orders use different measurement versions', function () {
    $customer = Customer::factory()->for($this->branch)->create();
    $v1 = approvedVersionFor($this->branch, $customer);
    $v2 = approvedVersionFor($this->branch, $customer);

    $res = $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customer->id, $v1->id, [
            'items' => [
                ['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $v1->id],
                ['product_type' => 'pant', 'quantity' => 1, 'measurement_version_id' => $v2->id],
            ],
        ]))->assertCreated();

    $versions = collect($res->json('data.items'))->pluck('measurement_version_id')->sort()->values()->all();
    expect($versions)->toBe([$v1->id, $v2->id]);
});

it('confirms an order built on a non-approved measurement (no approval gate)', function () {
    $customer = Customer::factory()->for($this->branch)->create();
    $pending = pendingVersionFor($this->branch, $customer);

    $res = $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', orderPayload($customer->id, $pending->id, [
            'items' => [['product_type' => 'shirt', 'quantity' => 1, 'measurement_version_id' => $pending->id]],
            'lifecycle_status' => 'intake_preparation',
        ]))->assertCreated();

    $orderId = $res->json('data.id');
    $itemId = $res->json('data.items.0.id');

    $this->withHeaders(bearer($this->fd))->getJson("/api/v1/orders/{$orderId}/items/{$itemId}/job-card")->assertCreated();
    $this->withHeaders(bearer($this->fd))->postJson("/api/v1/orders/{$orderId}/confirm", ['pricing' => ['total_amount' => 1000]])->assertOk();

    expect(Order::query()->find($orderId)->lifecycle_status)->toBe('order_received');
});

it('requires a measurement version on every sub-order', function () {
    $customer = Customer::factory()->for($this->branch)->create();

    $this->withHeaders(bearer($this->fd) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/api/v1/orders', [
            'customer_id' => $customer->id,
            'source' => 'walk_in',
            'delivery_mode' => 'pickup',
            'items' => [['product_type' => 'shirt', 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items.0.measurement_version_id']);
});

it('does not let Front Desk approve a measurement version', function () {
    $customer = Customer::factory()->for($this->branch)->create();
    $pending = pendingVersionFor($this->branch, $customer);

    $this->withHeaders(bearer($this->fd))
        ->postJson("/api/v1/measurements/versions/{$pending->id}/approve")
        ->assertStatus(403);
});
