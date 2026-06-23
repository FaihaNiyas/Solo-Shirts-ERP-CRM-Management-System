<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Exceptions\DamageException;
use App\Modules\Inventory\Jobs\GenerateDamageThumbnailJob;
use App\Modules\Inventory\Models\DamageReportPhoto;
use Illuminate\Http\UploadedFile;

/**
 * Stores damage-report photos on the 's3' disk and queues thumbnail generation.
 * Same pattern as QC photos — the stored path is private; callers only ever get
 * a temporary signed URL.
 */
final class DamagePhotoService
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const DISK = 's3';

    public function store(UploadedFile $file, User $actor): DamageReportPhoto
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw DamageException::photoTooLarge();
        }

        $path = $file->store('damage-reports', self::DISK);

        $photo = DamageReportPhoto::query()->create([
            'branch_id' => $actor->branch_id,
            'disk' => self::DISK,
            'path' => $path,
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $actor->id,
        ]);

        GenerateDamageThumbnailJob::dispatch($photo->id);

        return $photo;
    }
}
