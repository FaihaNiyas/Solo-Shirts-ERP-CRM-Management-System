<?php

declare(strict_types=1);

use App\Modules\Customer\Models\Customer;
use App\Modules\Finance\Models\InvoiceLine;
use App\Modules\Printing\Models\Document;
use App\Modules\Printing\Services\DocumentDataResolver;
use App\Modules\Printing\Services\PdfRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    Storage::fake('local');
    $this->branch = makeBranch(['code' => 'HQ']);
});

it('renders every PDF kind under fixtures', function () {
    $customer = Customer::factory()->for($this->branch)->create();

    $order = deliverableOrder($this->branch);
    $invoice = makeInvoice($this->branch);
    InvoiceLine::factory()->count(2)->create(['invoice_id' => $invoice->id]);
    $version = approvedVersionFor($this->branch, $customer);
    $delivery = makeDelivery($this->branch);

    $references = [
        Document::KIND_JOB_CARD => $order->id,
        Document::KIND_PACKING_SLIP => $order->id,
        Document::KIND_GST_INVOICE => $invoice->id,
        Document::KIND_MEASUREMENT_CARD => $version->id,
        Document::KIND_DELIVERY_RECEIPT => $delivery->id,
    ];

    $resolver = app(DocumentDataResolver::class);
    $renderer = app(PdfRenderer::class);

    foreach ($references as $kind => $referenceId) {
        $spec = $resolver->resolve($kind, $referenceId);
        $document = $renderer->render($spec);

        expect($document->kind)->toBe($kind)
            ->and($document->size_bytes)->toBeGreaterThan(0);
        Storage::disk('local')->assertExists($document->path);
    }

    expect(Document::query()->count())->toBe(5);
});
