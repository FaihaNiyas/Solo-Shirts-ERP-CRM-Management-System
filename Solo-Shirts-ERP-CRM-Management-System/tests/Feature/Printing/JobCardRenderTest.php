<?php

declare(strict_types=1);

use App\Modules\Order\Models\Order;
use App\Modules\Printing\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    Storage::fake('local');
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->staff = makeUser($this->branch, 'Front Desk');
});

it('renders a job card PDF, files a document row, and serves it over a signed URL', function () {
    $order = deliverableOrder($this->branch);

    $response = $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/documents/regenerate', [
            'kind' => Document::KIND_JOB_CARD,
            'reference_id' => $order->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.kind', Document::KIND_JOB_CARD);

    /** @var Document $document */
    $document = Document::query()->sole();
    expect($document->reference_type)->toBe(Order::class)
        ->and($document->reference_id)->toBe($order->id)
        ->and($document->size_bytes)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($document->path);

    // The signed URL in the resource downloads the bytes (public + signed).
    $url = (string) $response->json('data.download_url');
    $this->get($url)->assertOk();
});
