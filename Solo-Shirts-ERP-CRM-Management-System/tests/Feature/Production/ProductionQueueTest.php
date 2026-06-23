<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Measurement\Models\MeasurementProfile;
use App\Modules\Measurement\Models\MeasurementVersion;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
});

/**
 * A confirmed production item (order_received) with box + measurement + design.
 *
 * @param  array<string, mixed>  $attrs
 */
function prodItem($branch, string $state = 'cutting', array $attrs = []): OrderItem
{
    $customer = Customer::factory()->for($branch)->create(['name' => 'Prod Cust']);
    $order = Order::factory()->for($branch)->for($customer)->create([
        'lifecycle_status' => $attrs['__lifecycle'] ?? 'order_received',
        'expected_delivery_date' => today()->toDateString(),
    ]);
    unset($attrs['__lifecycle']);

    $profile = MeasurementProfile::factory()->for($branch)->for($customer)->create();
    $version = MeasurementVersion::factory()->for($branch)->for($profile, 'profile')->create([
        'shirt_data' => ['chest' => 40, 'shoulder' => 18],
    ]);

    return OrderItem::factory()->for($branch)->for($order)->create(array_merge([
        'state' => $state,
        'box_code' => 'BOX-1',
        'measurement_version_id' => $version->id,
        'design_notes' => ['fabric' => 'Cotton', 'style' => 'Slim', 'fit' => 'Regular', 'priority' => 'rush', 'notes' => 'handle gently'],
    ], $attrs));
}

it('lists confirmed production items and excludes intake + cancelled', function () {
    $cutting = prodItem($this->branch, 'cutting');
    prodItem($this->branch, 'draft', ['__lifecycle' => 'intake_preparation']); // intake — hidden
    prodItem($this->branch, 'cancelled'); // cancelled — hidden

    $res = $this->withHeaders(bearer($this->supervisor))->getJson('/api/v1/production/items')->assertOk();
    $ids = collect($res->json('data'))->pluck('item_id');
    expect($ids)->toHaveCount(1)->and($ids->first())->toBe($cutting->id);
});

it('returns rich queue rows with box, product type, fabric and rush flag', function () {
    $item = prodItem($this->branch, 'cutting');

    $row = collect($this->withHeaders(bearer($this->supervisor))->getJson('/api/v1/production/items')->json('data'))->firstWhere('item_id', $item->id);
    expect($row['production_box_code'])->toBe('BOX-1')
        ->and($row['fabric'])->toBe('Cotton')
        ->and($row['is_rush'])->toBeTrue()
        ->and($row['current_stage'])->toBe('cutting')
        ->and($row['blockers'])->toBe([]);
});

it('filters the queue by stage and item code', function () {
    $a = prodItem($this->branch, 'cutting', ['item_code' => 'ITM-AAA']);
    prodItem($this->branch, 'tailoring', ['item_code' => 'ITM-BBB']);

    $this->withHeaders(bearer($this->supervisor))->getJson('/api/v1/production/items?stage=cutting')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.item_id', $a->id);
    $this->withHeaders(bearer($this->supervisor))->getJson('/api/v1/production/items?item_code=AAA')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.item_id', $a->id);
});

it('returns the full workbench with measurement, fabric/style/fit and job card', function () {
    $item = prodItem($this->branch, 'cutting');

    $this->withHeaders(bearer($this->supervisor))->getJson("/api/v1/production/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.item_code', $item->item_code)
        ->assertJsonPath('data.product_type', 'shirt')
        ->assertJsonPath('data.production_box_code', 'BOX-1')
        ->assertJsonPath('data.fabric', 'Cotton')
        ->assertJsonPath('data.style', 'Slim')
        ->assertJsonPath('data.fit', 'Regular')
        ->assertJsonPath('data.measurement.chest', 40)
        ->assertJsonPath('data.measurement_version_id', $item->measurement_version_id)
        ->assertJsonPath('data.job_card_url', "/api/v1/orders/{$item->order_id}/items/{$item->id}/job-card");
});

it('permission-filters allowed_next_stages per role', function () {
    $cutter = makeUser($this->branch, 'Cutting Master');
    $tailor = makeUser($this->branch, 'Tailor');
    $item = prodItem($this->branch, 'fabric_allocated');

    // Cutting Master may push fabric_allocated → cutting.
    $cutterStages = $this->withHeaders(bearer($cutter))->getJson("/api/v1/production/items/{$item->id}")->json('data.allowed_next_stages');
    expect($cutterStages)->toContain('cutting');

    // A Tailor holds neither the cutting nor cancel permission here → no actions.
    $tailorStages = $this->withHeaders(bearer($tailor))->getJson("/api/v1/production/items/{$item->id}")->json('data.allowed_next_stages');
    expect($tailorStages)->toBe([]);
});

it('searches an item by box code and by order code', function () {
    $item = prodItem($this->branch, 'cutting', ['box_code' => 'BOX-SCAN']);
    $order = $item->order;

    $this->withHeaders(bearer($this->supervisor))->getJson('/api/v1/production/search-code?q=BOX-SCAN')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.item_id', $item->id);
    $this->withHeaders(bearer($this->supervisor))->getJson('/api/v1/production/search-code?q=' . $order->order_code)
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.item_id', $item->id);
});

it('blocks a transition on an intake (unconfirmed) order', function () {
    $admin = makeUser($this->branch, 'Admin');
    $item = prodItem($this->branch, 'fabric_allocated', ['__lifecycle' => 'intake_preparation']);

    transitionItem($this, $admin, $item->id, ['to' => 'cutting'])
        ->assertStatus(409)->assertJsonPath('code', 'ORDER_NOT_CONFIRMED');
});

it('lets the allowed role transition and records history, but forbids others', function () {
    $cutter = makeUser($this->branch, 'Cutting Master');
    $tailor = makeUser($this->branch, 'Tailor');
    $item = prodItem($this->branch, 'fabric_allocated');

    // Tailor lacks production.transition.cutting.
    transitionItem($this, $tailor, $item->id, ['to' => 'cutting'])->assertStatus(403);

    // Cutting Master may.
    transitionItem($this, $cutter, $item->id, ['to' => 'cutting'])->assertOk()->assertJsonPath('data.state', 'cutting');

    $this->withHeaders(bearer($cutter))->getJson("/api/v1/production/items/{$item->id}/history")
        ->assertOk()->assertJsonPath('data.0.to_state', 'cutting');
});

it('enforces branch scoping on the queue and workbench', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherItem = prodItem($other, 'cutting');
    prodItem($this->branch, 'cutting');

    // Queue: HQ supervisor never sees the OTHER-branch item.
    $ids = collect($this->withHeaders(bearer($this->supervisor))->getJson('/api/v1/production/items')->json('data'))->pluck('item_id');
    expect($ids)->not->toContain($otherItem->id);

    // Workbench: cross-branch item resolves to 404.
    $this->withHeaders(bearer($this->supervisor))->getJson("/api/v1/production/items/{$otherItem->id}")->assertStatus(404);
});

it('forbids the production queue without production.view (403)', function () {
    $accountant = makeUser($this->branch, 'Accountant'); // no production.view

    $this->withHeaders(bearer($accountant))->getJson('/api/v1/production/items')->assertStatus(403);
});
