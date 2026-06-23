<?php

declare(strict_types=1);

use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Services\ReportRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('records the error and marks the job failed when the report cannot run', function () {
    $job = ReportJob::factory()->for($this->branch)->create([
        'kind' => 'bogus_report',
        'status' => ReportJob::STATUS_PENDING,
    ]);

    app(ReportRunner::class)->run($job->fresh());

    $job->refresh();
    expect($job->status)->toBe(ReportJob::STATUS_FAILED)
        ->and($job->error)->toContain('bogus_report')
        ->and($job->document_id)->toBeNull()
        ->and($job->completed_at)->not->toBeNull();
});
