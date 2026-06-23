<?php

declare(strict_types=1);

use App\Modules\Printing\Http\Resources\DocumentResource;
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

it('rejects a download once the signed URL has expired and when unsigned', function () {
    $order = deliverableOrder($this->branch);

    $url = (string) $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/documents/regenerate', [
            'kind' => Document::KIND_JOB_CARD,
            'reference_id' => $order->id,
        ])
        ->assertCreated()
        ->json('data.download_url');

    // Valid while inside the TTL.
    $this->get($url)->assertOk();

    /** @var Document $document */
    $document = Document::query()->sole();

    // An unsigned URL (binding succeeds, signature missing) is forbidden.
    $this->getJson("/api/v1/documents/{$document->id}/download")->assertForbidden();

    // Past the 10-minute TTL the signature is no longer valid.
    $this->travel(DocumentResource::URL_TTL_MINUTES + 1)->minutes();
    $this->get($url)->assertForbidden();
});
