<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    // Production Supervisor holds production.dashboard.view (via ALL_PRODUCTION).
    $this->manager = makeUser($this->branch, 'Production Supervisor');
});

/** Insert an append-only transition row at a controlled time. */
function transitionRow(int $itemId, int $branchId, ?string $from, string $to, \Illuminate\Support\Carbon $at): void
{
    ProductionTransition::query()->create([
        'order_item_id' => $itemId,
        'branch_id' => $branchId,
        'from_state' => $from,
        'to_state' => $to,
        'idempotency_key' => (string) Str::uuid(),
        'occurred_at' => $at,
    ]);
}

it('returns per-stage counts and the key production totals', function () {
    productionItem($this->branch, 'cutting');
    productionItem($this->branch, 'cutting');
    productionItem($this->branch, 'qc');
    productionItem($this->branch, 'rework');
    productionItem($this->branch, 'ready_for_delivery');

    $this->withHeaders(bearer($this->manager))
        ->getJson('/api/v1/production/dashboard')
        ->assertOk()
        ->assertJsonPath('data.total_active', 5)
        ->assertJsonPath('data.by_stage.cutting', 2)
        ->assertJsonPath('data.pending_qc', 1)
        ->assertJsonPath('data.in_rework', 1)
        ->assertJsonPath('data.ready_for_delivery', 1);
});

it('counts delayed, urgent and on-hold items', function () {
    $delayed = productionItem($this->branch, 'cutting');
    $delayed->order->update(['expected_delivery_date' => now()->subDays(2)->toDateString()]);

    $urgent = productionItem($this->branch, 'tailoring');
    $urgent->order->update(['expected_delivery_date' => now()->addDays(5)->toDateString(), 'priority' => 'urgent']);

    $held = productionItem($this->branch, 'finishing');
    $held->order->update(['expected_delivery_date' => now()->addDays(5)->toDateString()]);
    $held->update(['on_hold_at' => now(), 'on_hold_reason' => 'awaiting parts']);

    $this->withHeaders(bearer($this->manager))
        ->getJson('/api/v1/production/dashboard')
        ->assertOk()
        ->assertJsonPath('data.delayed', 1)
        ->assertJsonPath('data.urgent', 1)
        ->assertJsonPath('data.on_hold', 1);
});

it('computes average dwell time per stage and the bottleneck', function () {
    $item = productionItem($this->branch, 'tailoring');
    $t0 = now()->subHours(10);

    // 2h in fabric_allocated, then 5h in cutting.
    transitionRow($item->id, $this->branch->id, 'draft', 'fabric_allocated', $t0);
    transitionRow($item->id, $this->branch->id, 'fabric_allocated', 'cutting', (clone $t0)->addHours(2));
    transitionRow($item->id, $this->branch->id, 'cutting', 'tailoring', (clone $t0)->addHours(7));

    $res = $this->withHeaders(bearer($this->manager))
        ->getJson('/api/v1/production/dashboard')
        ->assertOk();

    // JSON serialises 2.0 → 2, so compare loosely (value, not type).
    expect((float) $res->json('data.avg_hours_in_stage.fabric_allocated'))->toBe(2.0)
        ->and((float) $res->json('data.avg_hours_in_stage.cutting'))->toBe(5.0)
        ->and($res->json('data.bottleneck_stage.stage'))->toBe('cutting');
});

it('forbids Front Desk from the production dashboard (403)', function () {
    $frontDesk = makeUser($this->branch, 'Front Desk');

    $this->withHeaders(bearer($frontDesk))
        ->getJson('/api/v1/production/dashboard')
        ->assertForbidden();
});
