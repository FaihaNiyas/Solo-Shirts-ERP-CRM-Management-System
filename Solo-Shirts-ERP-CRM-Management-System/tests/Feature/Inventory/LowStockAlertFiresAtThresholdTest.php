<?php

declare(strict_types=1);

use App\Modules\Inventory\Jobs\LowStockAlertJob;
use App\Modules\Inventory\Models\FabricType;
use App\Modules\Inventory\Services\LowStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('fires a low-stock alert when a fabric type is below its threshold', function () {
    $type = FabricType::factory()->threshold(10.0)->create();
    ledgerRoll($this->branch, 4.0, $type); // 4 < 10 → low

    $count = (new LowStockAlertJob)->handle(app(LowStockService::class));

    expect($count)->toBe(1);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'inventory',
        'event' => 'low-stock',
    ]);
});

it('does not fire when stock is at or above the threshold', function () {
    $type = FabricType::factory()->threshold(10.0)->create();
    ledgerRoll($this->branch, 25.0, $type);

    expect((new LowStockAlertJob)->handle(app(LowStockService::class)))->toBe(0);
});

it('exposes low stock via the API, branch-scoped', function () {
    $type = FabricType::factory()->threshold(10.0)->create();
    ledgerRoll($this->branch, 4.0, $type);

    $viewer = makeUser($this->branch, 'Inventory Manager');

    $response = $this->withHeaders(bearer($viewer))
        ->getJson('/api/v1/inventory/low-stock')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.code'))->toBe($type->code);
});
