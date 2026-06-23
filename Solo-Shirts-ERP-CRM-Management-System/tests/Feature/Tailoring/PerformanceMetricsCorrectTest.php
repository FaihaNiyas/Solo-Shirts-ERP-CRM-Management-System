<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Models\Branch;
use App\Modules\Production\Models\TailorAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->viewer = makeUser($this->branch, 'Production Supervisor');
    $this->tailor = makeUser($this->branch, 'Tailor');
});

function completedAssignment(Branch $branch, User $tailor, int $pieces, string $startedAt, string $completedAt): void
{
    $bundle = cutBundleFor($branch, 'tailoring', $pieces);

    TailorAssignment::factory()->create([
        'bundle_id' => $bundle->id,
        'order_item_id' => $bundle->order_item_id,
        'branch_id' => $branch->id,
        'tailor_id' => $tailor->id,
        'status' => TailorAssignment::STATUS_COMPLETED,
        'started_at' => $startedAt,
        'completed_at' => $completedAt,
    ]);
}

it('computes bundles, pieces, average minutes per piece and on-time %', function () {
    $today = now()->toDateString();

    // 4 pieces in 60 min, then 2 pieces in 30 min → 6 pieces, 90 min, avg 15 min/piece.
    completedAssignment($this->branch, $this->tailor, 4, "{$today} 10:00:00", "{$today} 11:00:00");
    completedAssignment($this->branch, $this->tailor, 2, "{$today} 12:00:00", "{$today} 12:30:00");

    $response = $this->withHeaders(bearer($this->viewer))
        ->getJson("/api/v1/tailoring/performance/{$this->tailor->id}?from={$today}&to={$today}")
        ->assertOk();

    expect($response->json('data.bundles_completed'))->toBe(2)
        ->and($response->json('data.pieces_completed'))->toBe(6)
        ->and($response->json('data.avg_minutes_per_piece'))->toEqual(15.0)
        ->and($response->json('data.on_time_percentage'))->toEqual(100.0)
        ->and($response->json('data.rework_count'))->toBe(0);
});

it('excludes another branch\'s assignments from the figures', function () {
    $today = now()->toDateString();
    completedAssignment($this->branch, $this->tailor, 4, "{$today} 10:00:00", "{$today} 11:00:00");

    // Same tailor id space but a different branch's work must not leak in.
    $other = makeBranch(['code' => 'BR2']);
    completedAssignment($other, $this->tailor, 9, "{$today} 10:00:00", "{$today} 13:00:00");

    $response = $this->withHeaders(bearer($this->viewer))
        ->getJson("/api/v1/tailoring/performance/{$this->tailor->id}?from={$today}&to={$today}")
        ->assertOk();

    expect($response->json('data.bundles_completed'))->toBe(1)
        ->and($response->json('data.pieces_completed'))->toBe(4);
});

it('forbids viewing performance without the permission', function () {
    $stranger = makeUser($this->branch, 'Measurement Staff');

    $this->withHeaders(bearer($stranger))
        ->getJson("/api/v1/tailoring/performance/{$this->tailor->id}")
        ->assertForbidden();
});
