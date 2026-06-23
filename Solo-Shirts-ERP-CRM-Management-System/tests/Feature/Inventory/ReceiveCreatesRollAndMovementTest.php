<?php

declare(strict_types=1);

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Inventory\Models\FabricRoll;
use App\Modules\Inventory\Models\FabricType;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->manager = makeUser($this->branch, 'Inventory Manager');
    $this->type = FabricType::factory()->create();
    $this->supplier = Supplier::factory()->for($this->branch)->create();
});

it('receiving a placed PO creates a roll, a receive movement and a GRN', function () {
    $draft = $this->withHeaders(bearer($this->manager))
        ->postJson('/api/v1/inventory/purchase-orders', [
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['fabric_type_id' => $this->type->id, 'quantity_metres' => 50, 'unit_price_paise' => 20000, 'colour' => 'white'],
            ],
        ])
        ->assertCreated();

    $poId = $draft->json('data.id');
    $poItemId = $draft->json('data.items.0.id');

    $this->withHeaders(bearer($this->manager))
        ->postJson("/api/v1/inventory/purchase-orders/{$poId}/place")
        ->assertOk()
        ->assertJsonPath('data.status', 'placed');

    $this->withHeaders(bearer($this->manager))
        ->postJson("/api/v1/inventory/purchase-orders/{$poId}/receive", [
            'lines' => [
                ['purchase_order_item_id' => $poItemId, 'metres' => 50],
            ],
        ])
        ->assertCreated();

    $roll = FabricRoll::query()->sole();
    expect((float) $roll->remaining_metres)->toBe(50.0)
        ->and((float) $roll->received_length_metres)->toBe(50.0)
        ->and($roll->supplier_id)->toBe($this->supplier->id);

    expect(FabricMovement::query()->where('type', 'receive')->where('fabric_roll_id', $roll->id)->count())->toBe(1)
        ->and(PurchaseOrder::query()->find($poId)->status)->toBe('received');

    $this->assertDatabaseCount('grn', 1);
    $this->assertDatabaseCount('grn_items', 1);
});

it('cannot receive a PO that has not been placed (409)', function () {
    $draft = $this->withHeaders(bearer($this->manager))
        ->postJson('/api/v1/inventory/purchase-orders', [
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['fabric_type_id' => $this->type->id, 'quantity_metres' => 50, 'unit_price_paise' => 20000],
            ],
        ])->assertCreated();

    $poId = $draft->json('data.id');
    $poItemId = $draft->json('data.items.0.id');

    $this->withHeaders(bearer($this->manager))
        ->postJson("/api/v1/inventory/purchase-orders/{$poId}/receive", [
            'lines' => [['purchase_order_item_id' => $poItemId, 'metres' => 10]],
        ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'PO_NOT_PLACED');
});
