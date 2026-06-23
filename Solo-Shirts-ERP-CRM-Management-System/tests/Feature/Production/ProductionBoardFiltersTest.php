<?php

declare(strict_types=1);

use App\Modules\Production\Models\ProductionTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Production Supervisor');
});

/** Flatten all item ids across every board column. */
function boardItemIds(array $board): array
{
    $ids = [];
    foreach ($board['columns'] as $col) {
        foreach ($col['items'] as $item) {
            $ids[] = $item['id'];
        }
    }

    return $ids;
}

it('filters the board by customer name search', function () {
    $a = productionItem($this->branch, 'cutting');
    $a->order->customer->update(['name' => 'Zenith Couture']);
    $b = productionItem($this->branch, 'cutting');
    $b->order->customer->update(['name' => 'Other Tailors']);

    $res = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board?search=Zenith')
        ->assertOk();

    $ids = boardItemIds($res->json('data'));
    expect($ids)->toContain($a->id)->not->toContain($b->id);
});

it('filters the board by priority', function () {
    $urgent = productionItem($this->branch, 'tailoring');
    $urgent->order->update(['priority' => 'urgent']);
    $normal = productionItem($this->branch, 'tailoring'); // defaults to normal

    $res = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board?priority=urgent')
        ->assertOk();

    $ids = boardItemIds($res->json('data'));
    expect($ids)->toContain($urgent->id)->not->toContain($normal->id);
});

it('filters the board to delayed items', function () {
    $late = productionItem($this->branch, 'finishing');
    $late->order->update(['expected_delivery_date' => now()->subDays(3)->toDateString()]);
    $onTime = productionItem($this->branch, 'finishing');
    $onTime->order->update(['expected_delivery_date' => now()->addDays(3)->toDateString()]);

    $res = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board?delayed=1')
        ->assertOk();

    $ids = boardItemIds($res->json('data'));
    expect($ids)->toContain($late->id)->not->toContain($onTime->id);
});

it('filters the board to items that have been through rework', function () {
    $reworked = productionItem($this->branch, 'qc');
    ProductionTransition::query()->create([
        'order_item_id' => $reworked->id,
        'branch_id' => $this->branch->id,
        'from_state' => 'qc',
        'to_state' => 'rework',
        'idempotency_key' => (string) Str::uuid(),
        'occurred_at' => now()->subHour(),
    ]);
    $clean = productionItem($this->branch, 'qc');

    $res = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board?rework=1')
        ->assertOk();

    $ids = boardItemIds($res->json('data'));
    expect($ids)->toContain($reworked->id)->not->toContain($clean->id);
});

it('filters the board to ready-for-delivery items', function () {
    $ready = productionItem($this->branch, 'ready_for_delivery');
    $cutting = productionItem($this->branch, 'cutting');

    $res = $this->withHeaders(bearer($this->user))
        ->getJson('/api/v1/production/board?ready=1')
        ->assertOk();

    $ids = boardItemIds($res->json('data'));
    expect($ids)->toContain($ready->id)->not->toContain($cutting->id);
});
