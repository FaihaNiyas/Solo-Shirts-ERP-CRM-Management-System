<?php

declare(strict_types=1);

use App\Modules\Inventory\Contracts\StockLedgerInterface;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\FabricMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('does not mark a report approved if the stock ledger write fails', function () {
    seedRoles();
    $branch = makeBranch(['code' => 'HQ']);
    $approver = makeUser($branch, 'Admin');
    $roll = ledgerRoll($branch, 20.0);

    $report = DamageReport::factory()->for($branch)->create([
        'fabric_roll_id' => $roll->id,
        'quantity_lost_metres' => 5.0,
        'status' => 'pending',
    ]);

    // Force the ledger to blow up mid-approval; the whole transaction must roll back.
    $this->mock(StockLedgerInterface::class, function ($mock) {
        $mock->shouldReceive('record')->andThrow(new RuntimeException('ledger unavailable'));
    });

    $this->withHeaders(bearer($approver) + ['Idempotency-Key' => (string) Str::uuid()])
        ->postJson("/api/v1/damage-reports/{$report->id}/approve")
        ->assertStatus(500);

    // Report stayed pending and no write-off leaked through.
    expect($report->fresh()->status)->toBe('pending')
        ->and((float) $roll->fresh()->remaining_metres)->toBe(20.0)
        ->and(FabricMovement::query()->where('type', 'damage_writeoff')->count())->toBe(0);
});
