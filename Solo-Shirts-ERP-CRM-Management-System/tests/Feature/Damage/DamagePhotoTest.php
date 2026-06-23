<?php

declare(strict_types=1);

use App\Modules\Inventory\Jobs\GenerateDamageThumbnailJob;
use App\Modules\Inventory\Models\DamageReportPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->reporter = makeUser($this->branch, 'Inventory Manager');
});

it('uploads a damage photo to s3 and queues a thumbnail', function () {
    Storage::fake('s3');
    Queue::fake();

    $response = $this->withHeaders(bearer($this->reporter))
        ->post('/api/v1/damage-reports/photos', ['photo' => UploadedFile::fake()->image('d.jpg')->size(150)])
        ->assertCreated();

    $photo = DamageReportPhoto::query()->findOrFail($response->json('data.photo_id'));

    Storage::disk('s3')->assertExists($photo->path);
    Queue::assertPushedOn('thumbnails', GenerateDamageThumbnailJob::class);
});

it('rejects an oversize photo (413)', function () {
    Storage::fake('s3');

    $this->withHeaders(bearer($this->reporter))
        ->post('/api/v1/damage-reports/photos', ['photo' => UploadedFile::fake()->image('big.jpg')->size(6 * 1024)])
        ->assertStatus(413)
        ->assertJsonPath('code', 'PAYLOAD_TOO_LARGE');
});

it('links uploaded photos to the report and exposes them via signed URL', function () {
    Storage::fake('s3');
    Queue::fake();

    $photoId = $this->withHeaders(bearer($this->reporter))
        ->post('/api/v1/damage-reports/photos', ['photo' => UploadedFile::fake()->image('d.jpg')->size(120)])
        ->json('data.photo_id');

    $roll = ledgerRoll($this->branch, 20.0);

    $response = $this->withHeaders(bearer($this->reporter))
        ->postJson('/api/v1/damage-reports', [
            'fabric_roll_id' => $roll->id,
            'stage' => 'cutting',
            'damage_type' => 'tear',
            'quantity_lost_metres' => 2,
            'photo_ids' => [$photoId],
        ])
        ->assertCreated();

    $photo = $response->json('data.photos.0');
    expect($photo)->toHaveKeys(['id', 'url', 'has_thumbnail'])
        ->and($photo)->not->toHaveKey('path')
        ->and($photo['url'])->toContain('signature=');

    $parts = parse_url((string) $photo['url']);
    $this->get($parts['path'] . '?' . $parts['query'])->assertOk();
});
