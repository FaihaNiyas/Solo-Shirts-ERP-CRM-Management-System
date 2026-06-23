<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\FabricType;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->manager = makeUser($this->branch, 'Inventory Manager');
    $this->fd = makeUser($this->branch, 'Front Desk');
    $this->type = FabricType::factory()->create();
});

/** Draft + place a PO for one line, returning [poId, poItemId]. */
function placedPo($ctx, int $supplierId): array
{
    $draft = test()->withHeaders(bearer($ctx->manager))
        ->postJson('/api/v1/inventory/purchase-orders', [
            'supplier_id' => $supplierId,
            'items' => [['fabric_type_id' => $ctx->type->id, 'quantity_metres' => 40, 'unit_price_paise' => 15000, 'colour' => 'navy']],
        ])->assertCreated();

    $poId = $draft->json('data.id');
    test()->withHeaders(bearer($ctx->manager))->postJson("/api/v1/inventory/purchase-orders/{$poId}/place")->assertOk();

    return [$poId, $draft->json('data.items.0.id')];
}

it('lets an Inventory Manager create, list and update a supplier (PATCH)', function () {
    $created = $this->withHeaders(bearer($this->manager))->postJson('/api/v1/inventory/suppliers', [
        'code' => 'SUP1',
        'name' => 'Acme Textiles',
        'gstin' => '29ABCDE1234F1Z5',
        'phone' => '9876543210',
        'payment_terms' => 'Net 30',
    ])->assertCreated()->assertJsonPath('data.name', 'Acme Textiles');

    $id = $created->json('data.id');

    $this->withHeaders(bearer($this->manager))->getJson('/api/v1/inventory/suppliers')
        ->assertOk()->assertJsonPath('data.0.code', 'SUP1');

    $this->withHeaders(bearer($this->manager))->patchJson("/api/v1/inventory/suppliers/{$id}", [
        'name' => 'Acme Mills', 'is_active' => false,
    ])->assertOk()->assertJsonPath('data.name', 'Acme Mills')->assertJsonPath('data.is_active', false);
});

it('forbids Front Desk from managing suppliers or purchase orders (403)', function () {
    $supplier = Supplier::factory()->for($this->branch)->create();

    $this->withHeaders(bearer($this->fd))->postJson('/api/v1/inventory/suppliers', ['code' => 'X', 'name' => 'X'])->assertForbidden();
    $this->withHeaders(bearer($this->fd))->postJson('/api/v1/inventory/purchase-orders', [
        'supplier_id' => $supplier->id,
        'items' => [['fabric_type_id' => $this->type->id, 'quantity_metres' => 10, 'unit_price_paise' => 1000]],
    ])->assertForbidden();
});

it('shows a purchase order with its items and supplier name', function () {
    $supplier = Supplier::factory()->for($this->branch)->create(['name' => 'Nathan Fabrics']);
    [$poId] = placedPo($this, $supplier->id);

    $this->withHeaders(bearer($this->manager))->getJson("/api/v1/inventory/purchase-orders/{$poId}")
        ->assertOk()
        ->assertJsonPath('data.supplier_name', 'Nathan Fabrics')
        ->assertJsonPath('data.status', 'placed')
        ->assertJsonPath('data.items.0.quantity_metres', '40.00');
});

it('lists purchase history', function () {
    $supplier = Supplier::factory()->for($this->branch)->create();
    placedPo($this, $supplier->id);

    $this->withHeaders(bearer($this->manager))->getJson('/api/v1/inventory/purchase-orders')
        ->assertOk()
        ->assertJsonPath('data.0.supplier_name', $supplier->name);
});

it('receives a placed PO into a roll with an inward stock movement', function () {
    $supplier = Supplier::factory()->for($this->branch)->create();
    [$poId, $poItemId] = placedPo($this, $supplier->id);

    $this->withHeaders(bearer($this->manager))
        ->postJson("/api/v1/inventory/purchase-orders/{$poId}/receive", [
            'lines' => [['purchase_order_item_id' => $poItemId, 'metres' => 40, 'rack_location' => 'A-3']],
        ])
        ->assertCreated()
        ->assertJsonPath('data.purchase_order.status', 'received');

    $roll = FabricRoll::query()->sole();
    expect((float) $roll->remaining_metres)->toBe(40.0)
        ->and($roll->supplier_id)->toBe($supplier->id)
        ->and($roll->rack_location)->toBe('A-3')
        ->and(FabricMovement::query()->where('type', 'receive')->where('fabric_roll_id', $roll->id)->count())->toBe(1);
});

it('enforces branch scoping on purchase orders (404)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherManager = makeUser($other, 'Inventory Manager');
    $otherSupplier = Supplier::factory()->for($other)->create();
    [$foreignPo] = placedPo((object) ['manager' => $otherManager, 'type' => $this->type], $otherSupplier->id);

    $this->withHeaders(bearer($this->manager))->getJson("/api/v1/inventory/purchase-orders/{$foreignPo}")->assertNotFound();
});
