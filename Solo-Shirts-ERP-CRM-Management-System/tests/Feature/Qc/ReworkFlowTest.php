<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\DefectCategory;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\Models\QcDefect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->inspector = makeUser($this->branch, 'QC Supervisor');
});

it('a rework disposition sends the item back to rework and stores defects', function () {
    $item = productionItem($this->branch, 'qc');
    $category = DefectCategory::factory()->create();

    $this->withHeaders(bearer($this->inspector))
        ->postJson("/api/v1/qc/items/{$item->id}/inspect", [
            'disposition' => 'rework',
            'notes' => 'open seams on left sleeve',
            'defects' => [
                ['category_id' => $category->id, 'severity' => 'major', 'notes' => 'reopen and restitch'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.disposition', 'rework');

    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_REWORK)
        ->and(QcDefect::query()->count())->toBe(1);
});

it('blocks a 4th rework without the override permission (403 REWORK_LIMIT)', function () {
    $item = productionItem($this->branch, 'qc');

    // Three prior rework visits already recorded.
    ProductionTransition::factory()->count(3)->create([
        'order_item_id' => $item->id,
        'branch_id' => $this->branch->id,
        'to_state' => 'rework',
    ]);

    // An inspector who can inspect but cannot override.
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $plainInspector = User::factory()->create(['branch_id' => $this->branch->id]);
    $plainInspector->givePermissionTo('qc.inspect');

    $this->withHeaders(bearer($plainInspector))
        ->postJson("/api/v1/qc/items/{$item->id}/inspect", [
            'disposition' => 'rework',
            'notes' => 'still not right',
        ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'REWORK_LIMIT');

    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_QC);
});

it('allows a 4th rework via the override endpoint for a permitted user', function () {
    $item = productionItem($this->branch, 'qc');

    ProductionTransition::factory()->count(3)->create([
        'order_item_id' => $item->id,
        'branch_id' => $this->branch->id,
        'to_state' => 'rework',
    ]);

    $this->withHeaders(bearer($this->inspector))
        ->postJson("/api/v1/qc/items/{$item->id}/rework-override", ['notes' => 'supervisor override'])
        ->assertOk()
        ->assertJsonPath('data.state', 'rework');

    expect((string) $item->fresh()->state)->toBe(OrderItem::STATE_REWORK);
});

it('forbids the override endpoint without the override permission', function () {
    $item = productionItem($this->branch, 'qc');

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $plainInspector = User::factory()->create(['branch_id' => $this->branch->id]);
    $plainInspector->givePermissionTo('qc.inspect');

    $this->withHeaders(bearer($plainInspector))
        ->postJson("/api/v1/qc/items/{$item->id}/rework-override", [])
        ->assertForbidden();
});
