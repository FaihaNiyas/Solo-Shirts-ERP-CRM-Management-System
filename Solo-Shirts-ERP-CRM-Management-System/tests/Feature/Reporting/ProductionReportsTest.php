<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Models\ProductionTransition;
use App\Modules\Production\Models\QcInspection;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Reports\ProductionDailyCompletionReport;
use App\Modules\Reporting\Reports\ProductionDelayedReport;
use App\Modules\Reporting\Reports\ProductionQcFailReport;
use App\Modules\Reporting\Reports\ProductionReworkReport;
use App\Modules\Reporting\Reports\ProductionStagePendingReport;
use App\Modules\Reporting\Reports\ProductionSupervisorCompletedReport;
use App\Modules\Reporting\Services\ReportRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->actor = makeUser($this->branch, 'Admin');
});

/** Create an order_item in a given state, on an order with optional attributes. */
function reportItem(\App\Modules\Identity\Models\Branch $branch, string $state, array $orderAttrs = []): OrderItem
{
    $customer = Customer::factory()->for($branch)->create();
    $order = Order::factory()->for($branch)->for($customer)->create($orderAttrs);

    return OrderItem::factory()->for($branch)->for($order)->create(['state' => $state]);
}

/** Append a production transition row (the table only blocks UPDATEs, not INSERTs). */
function reportTransition(OrderItem $item, string $to, int $actorId, ?string $occurredAt = null): ProductionTransition
{
    return ProductionTransition::factory()->create([
        'order_item_id' => $item->id,
        'branch_id' => $item->branch_id,
        'to_state' => $to,
        'actor_id' => $actorId,
        'occurred_at' => $occurredAt ?? now(),
    ]);
}

it('registers all six production report kinds', function () {
    $kinds = app(ReportRunner::class)->kinds();

    expect($kinds)->toContain(
        'production_stage_pending',
        'production_delayed',
        'production_rework',
        'production_qc_fail',
        'production_supervisor_completed',
        'production_daily_completion',
    );
});

it('runs every production report to a CSV document', function (string $kind) {
    Storage::fake('local');

    $job = ReportJob::factory()->for($this->branch)->create([
        'kind' => $kind,
        'status' => ReportJob::STATUS_PENDING,
    ]);

    app(ReportRunner::class)->run($job->fresh());

    expect($job->fresh()->status)->toBe(ReportJob::STATUS_SUCCEEDED)
        ->and($job->fresh()->document_id)->not->toBeNull();
})->with([
    'production_stage_pending',
    'production_delayed',
    'production_rework',
    'production_qc_fail',
    'production_supervisor_completed',
    'production_daily_completion',
]);

it('counts pending items per stage', function () {
    reportItem($this->branch, OrderItem::STATE_TAILORING);
    reportItem($this->branch, OrderItem::STATE_TAILORING);
    reportItem($this->branch, OrderItem::STATE_CUTTING);
    reportItem($this->branch, OrderItem::STATE_DELIVERED); // terminal — excluded

    $rows = app(ProductionStagePendingReport::class)->rows([], $this->branch->id);
    $byStage = collect($rows)->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);

    expect($byStage['Tailoring'])->toBe(2)
        ->and($byStage['Cutting'])->toBe(1)
        ->and($byStage)->not->toHaveKey('Delivered');
});

it('lists only overdue, active items in the delayed report', function () {
    $overdue = reportItem($this->branch, OrderItem::STATE_TAILORING, [
        'expected_delivery_date' => now()->subDays(3)->toDateString(),
    ]);
    // Past-due but already delivered — not "delayed".
    reportItem($this->branch, OrderItem::STATE_DELIVERED, [
        'expected_delivery_date' => now()->subDays(5)->toDateString(),
    ]);
    // Active but not yet due.
    reportItem($this->branch, OrderItem::STATE_TAILORING, [
        'expected_delivery_date' => now()->addDays(5)->toDateString(),
    ]);

    $rows = app(ProductionDelayedReport::class)->rows([], $this->branch->id);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][0])->toBe($overdue->item_code)
        ->and($rows[0][5])->toBe(3); // days overdue
});

it('counts how many times each item was reworked', function () {
    $item = reportItem($this->branch, OrderItem::STATE_QC);
    reportTransition($item, OrderItem::STATE_REWORK, $this->actor->id);
    reportTransition($item, OrderItem::STATE_REWORK, $this->actor->id);
    reportTransition($item, OrderItem::STATE_QC, $this->actor->id); // forward move — not a rework

    $rows = app(ProductionReworkReport::class)->rows([], $this->branch->id);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][0])->toBe($item->item_code)
        ->and($rows[0][3])->toBe(2);
});

it('groups QC failures by reason and stage', function () {
    $item = reportItem($this->branch, OrderItem::STATE_QC);

    QcInspection::factory()->for($this->branch)->create([
        'order_item_id' => $item->id,
        'disposition' => QcInspection::DISPOSITION_REWORK,
        'failure_reason' => 'stitching_issue',
        'failure_stage' => OrderItem::STATE_TAILORING,
    ]);
    QcInspection::factory()->for($this->branch)->create([
        'order_item_id' => $item->id,
        'disposition' => QcInspection::DISPOSITION_REWORK,
        'failure_reason' => 'stitching_issue',
        'failure_stage' => OrderItem::STATE_TAILORING,
    ]);
    QcInspection::factory()->for($this->branch)->create([
        'order_item_id' => $item->id,
        'disposition' => QcInspection::DISPOSITION_PASS, // pass — excluded
    ]);

    $rows = app(ProductionQcFailReport::class)->rows([], $this->branch->id);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][0])->toBe('Stitching Issue')
        ->and($rows[0][1])->toBe('Tailoring')
        ->and($rows[0][2])->toBe(2);
});

it('counts completed transitions per supervisor, busiest first', function () {
    $busy = makeUser($this->branch);
    $light = makeUser($this->branch);
    $item = reportItem($this->branch, OrderItem::STATE_TAILORING);

    reportTransition($item, OrderItem::STATE_CUTTING, $busy->id);
    reportTransition($item, OrderItem::STATE_TAILORING, $busy->id);
    reportTransition($item, OrderItem::STATE_FINISHING, $light->id);

    $rows = app(ProductionSupervisorCompletedReport::class)->rows([], $this->branch->id);

    expect($rows[0][0])->toBe($busy->name)
        ->and($rows[0][1])->toBe(2)
        ->and($rows[1][0])->toBe($light->name)
        ->and($rows[1][1])->toBe(1);
});

it('buckets daily completions by day', function () {
    $item = reportItem($this->branch, OrderItem::STATE_READY_FOR_DELIVERY);
    reportTransition($item, OrderItem::STATE_READY_FOR_DELIVERY, $this->actor->id, now()->toDateTimeString());
    reportTransition($item, OrderItem::STATE_READY_FOR_DELIVERY, $this->actor->id, now()->toDateTimeString());
    reportTransition($item, OrderItem::STATE_PACKING, $this->actor->id); // not a completion

    $rows = app(ProductionDailyCompletionReport::class)->rows([], $this->branch->id);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][0])->toBe(now()->toDateString())
        ->and($rows[0][1])->toBe(2);
});

it('scopes production reports to the actor branch', function () {
    $other = makeBranch(['code' => 'OTHER']);
    reportItem($this->branch, OrderItem::STATE_TAILORING);
    reportItem($other, OrderItem::STATE_TAILORING);

    $rows = app(ProductionStagePendingReport::class)->rows([], $this->branch->id);
    $byStage = collect($rows)->mapWithKeys(fn ($r) => [$r[0] => $r[1]]);

    expect($byStage['Tailoring'])->toBe(1); // only this branch's item
});
