<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Inventory\Http\Requests\UploadDamagePhotoRequest;
use App\Modules\Inventory\Models\DamageReport;
use App\Modules\Inventory\Models\DamageReportPhoto;
use App\Modules\Inventory\Services\DamagePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DamageReportPhotoController extends BaseApiController
{
    public function __construct(private readonly DamagePhotoService $photos) {}

    public function store(UploadDamagePhotoRequest $request): JsonResponse
    {
        $this->authorize('create', DamageReport::class);

        /** @var User $actor */
        $actor = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('photo');

        $photo = $this->photos->store($file, $actor);

        return $this->respond(['photo_id' => $photo->id], 'Photo uploaded', 201);
    }

    /**
     * Public but signature-protected; the raw storage path is never shared.
     */
    public function download(DamageReportPhoto $photo): StreamedResponse
    {
        $disk = $photo->storageDisk();

        abort_unless($disk->exists($photo->path), 404);

        return $disk->response($photo->path);
    }
}
