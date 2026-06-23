<?php

declare(strict_types=1);

use App\Modules\Production\Models\DefectCategory;
use App\Modules\Production\Models\QcDefectPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->inspector = makeUser($this->branch, 'QC Supervisor');
});

it('exposes defect photos via a temporary signed URL, never the raw path', function () {
    Storage::fake('s3');
    Queue::fake();

    $category = DefectCategory::factory()->create();

    $photoId = $this->withHeaders(bearer($this->inspector))
        ->post('/api/v1/qc/photos', ['photo' => UploadedFile::fake()->image('d.jpg')->size(120)])
        ->assertCreated()
        ->json('data.photo_id');

    $item = productionItem($this->branch, 'qc');

    $response = $this->withHeaders(bearer($this->inspector))
        ->postJson("/api/v1/qc/items/{$item->id}/inspect", [
            'disposition' => 'rework',
            'notes' => 'see photo',
            'defects' => [
                ['category_id' => $category->id, 'severity' => 'major', 'photo_ids' => [$photoId]],
            ],
        ])
        ->assertCreated();

    $photo = $response->json('data.defects.0.photos.0');

    expect($photo)->toHaveKeys(['id', 'url', 'has_thumbnail'])
        ->and($photo)->not->toHaveKey('path')
        ->and($photo['url'])->toContain('signature=');

    // The raw storage path must not leak anywhere in the payload.
    $rawPath = QcDefectPhoto::query()->findOrFail($photoId)->path;
    expect($response->getContent())->not->toContain($rawPath);

    // The signed URL actually resolves the file.
    $parts = parse_url((string) $photo['url']);
    $this->get($parts['path'] . '?' . $parts['query'])->assertOk();
});

it('refuses an unsigned download (403)', function () {
    Storage::fake('s3');

    $photoId = $this->withHeaders(bearer($this->inspector))
        ->post('/api/v1/qc/photos', ['photo' => UploadedFile::fake()->image('d.jpg')->size(120)])
        ->json('data.photo_id');

    // Hitting the real download path without a valid signature is rejected.
    $this->get("/api/v1/qc/photos/{$photoId}/download")->assertForbidden();
});
