<?php

declare(strict_types=1);

use App\Modules\Production\Jobs\GenerateThumbnailJob;
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

it('rejects a non-image upload (422)', function () {
    Storage::fake('s3');

    $file = UploadedFile::fake()->create('malware.exe', 50, 'application/x-msdownload');

    $this->withHeaders(bearer($this->inspector))
        ->post('/api/v1/qc/photos', ['photo' => $file])
        ->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED');
});

it('rejects an oversize image (413 PAYLOAD_TOO_LARGE)', function () {
    Storage::fake('s3');

    // 6 MB image — over the 5 MB cap.
    $file = UploadedFile::fake()->image('huge.jpg')->size(6 * 1024);

    $this->withHeaders(bearer($this->inspector))
        ->post('/api/v1/qc/photos', ['photo' => $file])
        ->assertStatus(413)
        ->assertJsonPath('code', 'PAYLOAD_TOO_LARGE');
});

it('stores a valid image on s3 and queues a thumbnail', function () {
    Storage::fake('s3');
    Queue::fake();

    $file = UploadedFile::fake()->image('defect.jpg')->size(200);

    $response = $this->withHeaders(bearer($this->inspector))
        ->post('/api/v1/qc/photos', ['photo' => $file])
        ->assertCreated();

    $photoId = $response->json('data.photo_id');
    $photo = QcDefectPhoto::query()->findOrFail($photoId);

    Storage::disk('s3')->assertExists($photo->path);
    Queue::assertPushed(GenerateThumbnailJob::class, fn (GenerateThumbnailJob $job): bool => $job->photoId === $photo->id);

    // The low-priority queue is used.
    Queue::assertPushedOn('thumbnails', GenerateThumbnailJob::class);
});
