<?php

declare(strict_types=1);

use App\Modules\Finance\Models\InvoiceLine;
use App\Modules\Printing\Jobs\RenderPdfJob;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Services\DocumentDataResolver;
use App\Modules\Printing\Services\PdfRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Front Desk');
});

it('queues a heavy invoice (>50 lines) instead of rendering inline', function () {
    Queue::fake();

    $invoice = makeInvoice($this->branch);
    InvoiceLine::factory()->count(51)->create(['invoice_id' => $invoice->id]);

    $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/documents/regenerate', [
            'kind' => Document::KIND_GST_INVOICE,
            'reference_id' => $invoice->id,
        ])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'queued');

    Queue::assertPushed(RenderPdfJob::class, function (RenderPdfJob $job) use ($invoice): bool {
        return $job->kind === Document::KIND_GST_INVOICE && $job->referenceId === $invoice->id;
    });

    // Nothing rendered synchronously.
    expect(Document::query()->count())->toBe(0);
});

it('renders a small invoice synchronously', function () {
    Storage::fake('local');

    $invoice = makeInvoice($this->branch);
    InvoiceLine::factory()->count(3)->create(['invoice_id' => $invoice->id]);

    $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/documents/regenerate', [
            'kind' => Document::KIND_GST_INVOICE,
            'reference_id' => $invoice->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.kind', Document::KIND_GST_INVOICE);

    expect(Document::query()->count())->toBe(1);
});

it('renders the queued invoice and notifies on completion when the job runs', function () {
    Storage::fake('local');
    $fake = fakeNotifications();

    $invoice = makeInvoice($this->branch);
    InvoiceLine::factory()->count(51)->create(['invoice_id' => $invoice->id]);

    (new RenderPdfJob(Document::KIND_GST_INVOICE, $invoice->id, $this->staff->id))
        ->handle(
            app(DocumentDataResolver::class),
            app(PdfRenderer::class),
            $fake,
        );

    expect(Document::query()->where('kind', Document::KIND_GST_INVOICE)->count())->toBe(1)
        ->and($fake->sent)->toHaveCount(1)
        ->and($fake->sent[0]['payload']['kind'])->toBe(Document::KIND_GST_INVOICE);
});
