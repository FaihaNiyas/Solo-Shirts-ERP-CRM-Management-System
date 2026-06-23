<?php

declare(strict_types=1);

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Production\Exceptions\QcException;
use App\Modules\Production\Jobs\GenerateThumbnailJob;
use App\Modules\Production\Models\QcDefectPhoto;
use Illuminate\Http\UploadedFile;

/**
 * Stores defect photos on the 's3' disk and queues thumbnail generation. The
 * stored path is private; callers only ever receive a temporary signed URL.
 */
final class DefectPhotoService
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const DISK = 's3';

    public function store(UploadedFile $file, User $actor): QcDefectPhoto
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw QcException::photoTooLarge();
        }

        $path = $file->store('qc-defects', self::DISK);

        $photo = QcDefectPhoto::query()->create([
            'branch_id' => $actor->branch_id,
            'disk' => self::DISK,
            'path' => $path,
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $actor->id,
        ]);

        GenerateThumbnailJob::dispatch($photo->id);

        return $photo;
    }
}
