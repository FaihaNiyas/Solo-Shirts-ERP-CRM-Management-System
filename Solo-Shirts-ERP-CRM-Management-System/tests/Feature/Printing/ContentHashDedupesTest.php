<?php

declare(strict_types=1);

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

it('reuses the same document when identical input is re-rendered', function () {
    $order = deliverableOrder($this->branch);

    $first = $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/documents/regenerate', [
            'kind' => Document::KIND_JOB_CARD,
            'reference_id' => $order->id,
        ])
        ->assertCreated();

    $second = $this->withHeaders(bearer($this->staff))
        ->postJson('/api/v1/documents/regenerate', [
            'kind' => Document::KIND_JOB_CARD,
            'reference_id' => $order->id,
        ])
        ->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and($second->json('data.content_hash'))->toBe($first->json('data.content_hash'));

    // Exactly one document row and one stored file.
    expect(Document::query()->count())->toBe(1);
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);
});
