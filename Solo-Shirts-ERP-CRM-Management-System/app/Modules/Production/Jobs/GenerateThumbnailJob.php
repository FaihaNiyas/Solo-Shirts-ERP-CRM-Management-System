<?php

declare(strict_types=1);

namespace App\Modules\Production\Jobs;

use App\Modules\Production\Models\QcDefectPhoto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a thumbnail for a defect photo on a low-priority queue. The actual
 * image resize is a stub here (no image library dependency); it writes a derived
 * thumb object and records its path so retrieval can prefer it.
 */
final class GenerateThumbnailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $photoId)
    {
        $this->onQueue('thumbnails');
    }

    public function handle(): void
    {
        $photo = QcDefectPhoto::query()->withoutGlobalScopes()->find($this->photoId);

        if ($photo === null) {
            return;
        }

        $disk = Storage::disk($photo->disk);

        if (!$disk->exists($photo->path)) {
            return;
        }

        $thumbPath = 'thumbnails/' . basename($photo->path);
        $disk->put($thumbPath, $disk->get($photo->path));

        $photo->update(['thumb_path' => $thumbPath]);
    }
}
