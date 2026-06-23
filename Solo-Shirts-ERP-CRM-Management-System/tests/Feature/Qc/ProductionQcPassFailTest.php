<?php

declare(strict_types=1);

use App\Modules\Production\Models\QcInspection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->qc = makeUser($this->branch, 'QC Supervisor');
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
    $this->fd = makeUser($this->branch, 'Front Desk');
});

function qcPass($test, $user, int $itemId, array $body = [])
{
    return $test->withHeaders(bearer($user))->postJson("/api/v1/production/items/{$itemId}/qc/pass", $body);
}

function qcFail($test, $user, int $itemId, array $body)
{
    return $test->withHeaders(bearer($user))->postJson("/api/v1/production/items/{$itemId}/qc/fail", $body);
}

it('passes QC, records the inspection and moves the item to packing', function () {
    $item = productionItem($this->branch, 'qc');

    qcPass($this, $this->qc, $item->id, ['notes' => 'clean finish'])
        ->assertCreated()
        ->assertJsonPath('data.result', 'passed')
        ->assertJsonPath('data.disposition', 'pass');

    expect((string) $item->fresh()->state)->toBe('packing')
        ->and(QcInspection::query()->where('order_item_id', $item->id)->where('disposition', 'pass')->count())->toBe(1);
});

it('fails QC, records reason + target and parks the item in rework', function () {
    $item = productionItem($this->branch, 'qc');

    qcFail($this, $this->qc, $item->id, [
        'failure_reason' => 'stitching_issue',
        'rework_target_stage' => 'tailoring',
        'notes' => 'Sleeve seam not straight',
    ])
        ->assertCreated()
        ->assertJsonPath('data.result', 'failed')
        ->assertJsonPath('data.failure_reason', 'stitching_issue')
        ->assertJsonPath('data.rework_target_stage', 'tailoring');

    expect((string) $item->fresh()->state)->toBe('rework')
        ->and(QcInspection::query()->where('order_item_id', $item->id)->where('disposition', 'rework')->exists())->toBeTrue();
});

it('requires a failure reason to fail QC (422)', function () {
    $item = productionItem($this->branch, 'qc');

    qcFail($this, $this->qc, $item->id, ['rework_target_stage' => 'tailoring'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');

    expect((string) $item->fresh()->state)->toBe('qc');
});

it('blocks an invalid rework target stage (422)', function () {
    $item = productionItem($this->branch, 'qc');

    qcFail($this, $this->qc, $item->id, ['failure_reason' => 'stitching_issue', 'rework_target_stage' => 'delivered'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});

it('cannot QC an item whose order is still in intake preparation (409)', function () {
    $item = productionItem($this->branch, 'qc');
    $item->order->forceFill(['lifecycle_status' => 'intake_preparation'])->save();

    qcPass($this, $this->qc, $item->id)
        ->assertStatus(409)
        ->assertJsonPath('code', 'ORDER_NOT_CONFIRMED');
});

it('cannot QC a cancelled item (409 NOT_IN_QC)', function () {
    $item = productionItem($this->branch, 'cancelled');

    qcPass($this, $this->qc, $item->id)->assertStatus(409)->assertJsonPath('code', 'NOT_IN_QC');
});

it('cannot QC a delivered item (409 NOT_IN_QC)', function () {
    $item = productionItem($this->branch, 'delivered');

    qcPass($this, $this->qc, $item->id)->assertStatus(409)->assertJsonPath('code', 'NOT_IN_QC');
});

it('cannot pass/fail an item outside the QC state (409 NOT_IN_QC)', function () {
    $item = productionItem($this->branch, 'tailoring');

    qcPass($this, $this->qc, $item->id)->assertStatus(409)->assertJsonPath('code', 'NOT_IN_QC');
    qcFail($this, $this->qc, $item->id, ['failure_reason' => 'stitching_issue', 'rework_target_stage' => 'tailoring'])
        ->assertStatus(409)->assertJsonPath('code', 'NOT_IN_QC');
});

it('lets a rework item return to QC and pass after the fix', function () {
    $item = productionItem($this->branch, 'qc');

    qcFail($this, $this->qc, $item->id, ['failure_reason' => 'stitching_issue', 'rework_target_stage' => 'tailoring']);
    expect((string) $item->fresh()->state)->toBe('rework');

    // Re-inspect path: rework → qc (existing edge), then pass → packing.
    transitionItem($this, $this->qc, $item->id, ['to' => 'qc', 'notes' => 'fix verified'])->assertOk();
    qcPass($this, $this->qc, $item->id)->assertCreated();

    expect((string) $item->fresh()->state)->toBe('packing')
        ->and(QcInspection::query()->where('order_item_id', $item->id)->count())->toBe(2);
});

it('allows routing a rework item back to its target stage (rework → tailoring)', function () {
    $item = productionItem($this->branch, 'qc');
    qcFail($this, $this->qc, $item->id, ['failure_reason' => 'stitching_issue', 'rework_target_stage' => 'tailoring']);

    // Phase 7C edge: a supervisor can send the parked item back to the fix stage.
    transitionItem($this, $this->supervisor, $item->id, ['to' => 'tailoring', 'notes' => 're-stitch'])
        ->assertOk()
        ->assertJsonPath('data.state', 'tailoring');
});

it('surfaces QC status, rework context and history on the workbench', function () {
    $item = productionItem($this->branch, 'qc');
    qcFail($this, $this->qc, $item->id, ['failure_reason' => 'stitching_issue', 'rework_target_stage' => 'tailoring', 'notes' => 'seam']);

    $this->withHeaders(bearer($this->qc))->getJson("/api/v1/production/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.qc.in_rework', true)
        ->assertJsonPath('data.qc.rework.target_stage', 'tailoring')
        ->assertJsonPath('data.qc.rework.failure_reason', 'stitching_issue')
        ->assertJsonPath('data.qc.attempts', 1);

    $this->withHeaders(bearer($this->qc))->getJson("/api/v1/production/items/{$item->id}/qc")
        ->assertOk()
        ->assertJsonPath('data.in_rework', true)
        ->assertJsonCount(1, 'data.history');
});

it('forbids Front Desk from passing or failing QC (403)', function () {
    $item = productionItem($this->branch, 'qc');

    qcPass($this, $this->fd, $item->id)->assertForbidden();
    qcFail($this, $this->fd, $item->id, ['failure_reason' => 'stitching_issue', 'rework_target_stage' => 'tailoring'])
        ->assertForbidden();

    expect((string) $item->fresh()->state)->toBe('qc')
        ->and(QcInspection::query()->count())->toBe(0);
});

it('enforces branch scoping on the QC endpoints (404)', function () {
    $other = makeBranch(['code' => 'OTHER']);
    $otherItem = productionItem($other, 'qc');

    qcPass($this, $this->qc, $otherItem->id)->assertNotFound();
    $this->withHeaders(bearer($this->qc))->getJson("/api/v1/production/items/{$otherItem->id}/qc")->assertNotFound();
});

it('creates no customer alteration and no invoice/payment when QC fails', function () {
    $item = productionItem($this->branch, 'qc');

    qcFail($this, $this->qc, $item->id, ['failure_reason' => 'fabric_damage', 'rework_target_stage' => 'cutting'])
        ->assertCreated();

    expect(DB::table('alteration_requests')->count())->toBe(0)
        ->and(DB::table('invoices')->count())->toBe(0)
        ->and(DB::table('payments')->count())->toBe(0);
});

it('keeps a rework item out of the ready-rack search', function () {
    $item = productionItem($this->branch, 'rework');
    $orderCode = $item->order->order_code;

    $this->withHeaders(bearer($this->fd))->getJson('/api/v1/rack/search?q=' . $orderCode)
        ->assertOk()
        ->assertJsonPath('data.results.0.order_code', $orderCode)
        ->assertJsonPath('data.results.0.ready', false)
        ->assertJsonCount(0, 'data.results.0.ready_sub_orders');
});
