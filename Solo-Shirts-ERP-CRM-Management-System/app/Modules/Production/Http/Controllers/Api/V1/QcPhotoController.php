<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Production\Http\Requests\UploadPhotoRequest;
use App\Modules\Production\Models\QcDefectPhoto;
use App\Modules\Production\Services\DefectPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class QcPhotoController extends BaseApiController
{
    public function __construct(private readonly DefectPhotoService $photos) {}

    public function store(UploadPhotoRequest $request): JsonResponse
    {
        $this->authorize('inspect', OrderItem::class);

        /** @var User $actor */
        $actor = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('photo');

        $photo = $this->photos->store($file, $actor);

        return $this->respond(['photo_id' => $photo->id], 'Photo uploaded', 201);
    }

    /**
     * Public but signature-protected (no raw path is ever shared).
     */
    public function download(QcDefectPhoto $photo): StreamedResponse
    {
        $disk = $photo->storageDisk();

        abort_unless($disk->exists($photo->path), 404);

        return $disk->response($photo->path);
    }
}
