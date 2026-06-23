<?php

declare(strict_types=1);

use App\Modules\Reporting\Jobs\RunReportJob;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Reporting\Services\ReportRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->actor = makeUser($this->branch, 'Admin');
});

it('records a pending job and queues the work', function () {
    Queue::fake();

    $job = app(ReportRunner::class)->request('orders', [], $this->actor);

    expect($job->status)->toBe(ReportJob::STATUS_PENDING);
    Queue::assertPushed(RunReportJob::class, fn (RunReportJob $j): bool => $j->reportJobId === $job->id);
});

it('runs to succeeded and attaches a document', function () {
    Storage::fake('local');

    $job = ReportJob::factory()->for($this->branch)->create([
        'kind' => 'orders',
        'status' => ReportJob::STATUS_PENDING,
    ]);

    app(ReportRunner::class)->run($job->fresh());

    $job->refresh();
    expect($job->status)->toBe(ReportJob::STATUS_SUCCEEDED)
        ->and($job->document_id)->not->toBeNull()
        ->and($job->completed_at)->not->toBeNull();

    $this->assertDatabaseHas('documents', ['id' => $job->document_id, 'kind' => 'report']);
});

it('drives the full lifecycle via the queued job', function () {
    Storage::fake('local');

    $job = ReportJob::factory()->for($this->branch)->create([
        'kind' => 'finance_summary',
        'status' => ReportJob::STATUS_PENDING,
    ]);

    (new RunReportJob($job->id))->handle(app(ReportRunner::class));

    expect($job->fresh()->status)->toBe(ReportJob::STATUS_SUCCEEDED);
});
