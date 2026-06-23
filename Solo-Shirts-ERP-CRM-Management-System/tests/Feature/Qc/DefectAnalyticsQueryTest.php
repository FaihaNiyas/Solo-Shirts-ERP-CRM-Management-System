<?php

declare(strict_types=1);

use App\Modules\Production\Models\DefectCategory;
use App\Modules\Production\Models\QcDefect;
use App\Modules\Production\Models\QcInspection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->viewer = makeUser($this->branch, 'QC Supervisor');
});

function defectOnCategory(int $inspectionId, int $categoryId, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        QcDefect::query()->create([
            'qc_inspection_id' => $inspectionId,
            'defect_category_id' => $categoryId,
            'severity' => 'minor',
        ]);
    }
}

it('returns the top defect categories over the last 30 days, branch-scoped', function () {
    $stitch = DefectCategory::factory()->create(['code' => 'stitch_open', 'name' => 'Stitch Open']);
    $stain = DefectCategory::factory()->create(['code' => 'stain', 'name' => 'Stain']);

    $item = productionItem($this->branch, 'qc');
    $inspection = QcInspection::factory()->create([
        'branch_id' => $this->branch->id,
        'order_item_id' => $item->id,
        'inspected_at' => now()->subDays(2),
    ]);

    defectOnCategory($inspection->id, $stitch->id, 3);
    defectOnCategory($inspection->id, $stain->id, 1);

    // Old defect outside the window must be excluded.
    $old = QcInspection::factory()->create([
        'branch_id' => $this->branch->id,
        'order_item_id' => $item->id,
        'inspected_at' => now()->subDays(60),
    ]);
    defectOnCategory($old->id, $stain->id, 5);

    $response = $this->withHeaders(bearer($this->viewer))
        ->getJson('/api/v1/qc/defects/analytics?days=30')
        ->assertOk();

    $data = $response->json('data');

    expect($data)->toHaveCount(2)
        ->and($data[0]['code'])->toBe('stitch_open')
        ->and($data[0]['defect_count'])->toBe(3)
        ->and($data[1]['code'])->toBe('stain')
        ->and($data[1]['defect_count'])->toBe(1);
});

it('forbids analytics without QC permission', function () {
    $stranger = makeUser($this->branch, 'Front Desk');

    $this->withHeaders(bearer($stranger))
        ->getJson('/api/v1/qc/defects/analytics')
        ->assertForbidden();
});
