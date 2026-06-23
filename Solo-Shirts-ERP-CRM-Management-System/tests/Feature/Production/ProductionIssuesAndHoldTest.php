<?php

declare(strict_types=1);

use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionIssue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    // Production Supervisor owns reporting, resolving and holds.
    $this->supervisor = makeUser($this->branch, 'Production Supervisor');
});

// --- Issues ---------------------------------------------------------------

it('reports a text-only issue without moving the production state', function () {
    $item = productionItem($this->branch, 'tailoring');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/issues", [
            'issue_type' => ProductionIssue::TYPE_MACHINE,
            'description' => 'Sewing machine needle keeps breaking.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', ProductionIssue::STATUS_OPEN)
        ->assertJsonPath('data.stage', 'tailoring')
        ->assertJsonPath('data.issue_type', ProductionIssue::TYPE_MACHINE);

    // State is untouched — issues never drive the machine.
    expect((string) $item->fresh()->state)->toBe('tailoring');
    expect(ProductionIssue::query()->where('order_item_id', $item->id)->count())->toBe(1);
});

it('requires an issue type and description (422)', function () {
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/issues", ['description' => ''])
        ->assertStatus(422);
});

it('resolves an open issue and stamps the resolver', function () {
    $item = productionItem($this->branch, 'qc');
    $issue = ProductionIssue::query()->create([
        'order_item_id' => $item->id,
        'branch_id' => $this->branch->id,
        'stage' => 'qc',
        'issue_type' => ProductionIssue::TYPE_QUALITY,
        'description' => 'Loose button.',
        'status' => ProductionIssue::STATUS_OPEN,
        'reported_by' => $this->supervisor->id,
    ]);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/issues/{$issue->id}/resolve", ['notes' => 'Re-stitched.'])
        ->assertOk()
        ->assertJsonPath('data.status', ProductionIssue::STATUS_RESOLVED);

    $fresh = $issue->fresh();
    expect($fresh->resolved_by)->toBe($this->supervisor->id)
        ->and($fresh->resolved_at)->not->toBeNull()
        ->and($fresh->resolution_notes)->toBe('Re-stitched.');
});

it('rejects resolving an already-resolved issue (409)', function () {
    $item = productionItem($this->branch, 'qc');
    $issue = ProductionIssue::query()->create([
        'order_item_id' => $item->id,
        'branch_id' => $this->branch->id,
        'stage' => 'qc',
        'issue_type' => ProductionIssue::TYPE_OTHER,
        'description' => 'x',
        'status' => ProductionIssue::STATUS_RESOLVED,
        'reported_by' => $this->supervisor->id,
        'resolved_by' => $this->supervisor->id,
        'resolved_at' => now(),
    ]);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/issues/{$issue->id}/resolve", [])
        ->assertStatus(409);
});

it('forbids Front Desk from reporting an issue (403)', function () {
    $frontDesk = makeUser($this->branch, 'Front Desk');
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($frontDesk))
        ->postJson("/api/v1/production/items/{$item->id}/issues", [
            'issue_type' => ProductionIssue::TYPE_OTHER,
            'description' => 'should not be allowed',
        ])
        ->assertForbidden();
});

it('surfaces the open issue count on the item resource', function () {
    $item = productionItem($this->branch, 'tailoring');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/issues", [
            'issue_type' => ProductionIssue::TYPE_MATERIAL,
            'description' => 'Fabric flaw near collar.',
        ])->assertCreated();

    $this->withHeaders(bearer($this->supervisor))
        ->getJson("/api/v1/production/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.issue_count', 1)
        ->assertJsonPath('data.issues.open_count', 1);
});

// --- On hold --------------------------------------------------------------

it('puts an item on hold and resumes it without changing state', function () {
    $item = productionItem($this->branch, 'finishing');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/hold", ['reason' => 'Awaiting fabric'])
        ->assertOk()
        ->assertJsonPath('data.is_on_hold', true)
        ->assertJsonPath('data.on_hold_reason', 'Awaiting fabric');

    expect($item->fresh()->isOnHold())->toBeTrue();
    expect((string) $item->fresh()->state)->toBe('finishing');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/resume")
        ->assertOk()
        ->assertJsonPath('data.is_on_hold', false);

    expect($item->fresh()->isOnHold())->toBeFalse();
});

it('rejects holding an already-held item (409)', function () {
    $item = productionItem($this->branch, 'cutting');
    $item->update(['on_hold_at' => now(), 'on_hold_reason' => 'first']);

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/hold", ['reason' => 'again'])
        ->assertStatus(409);
});

it('rejects resuming an item that is not on hold (409)', function () {
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/resume")
        ->assertStatus(409);
});

it('cannot hold a cancelled item (409)', function () {
    $item = productionItem($this->branch, 'cancelled');

    $this->withHeaders(bearer($this->supervisor))
        ->postJson("/api/v1/production/items/{$item->id}/hold", ['reason' => 'nope'])
        ->assertStatus(409);
});

it('forbids Front Desk from holding an item (403)', function () {
    $frontDesk = makeUser($this->branch, 'Front Desk');
    $item = productionItem($this->branch, 'cutting');

    $this->withHeaders(bearer($frontDesk))
        ->postJson("/api/v1/production/items/{$item->id}/hold", ['reason' => 'x'])
        ->assertForbidden();
});
