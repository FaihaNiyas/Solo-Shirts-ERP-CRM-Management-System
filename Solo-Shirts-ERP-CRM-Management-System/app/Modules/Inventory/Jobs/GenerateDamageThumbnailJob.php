<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Models\DamageReportPhoto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a thumbnail for a damage-report photo on the low-priority queue. The
 * resize itself is a stub (no image-library dependency).
 */
final class GenerateDamageThumbnailJob implements ShouldQueue
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
        $photo = DamageReportPhoto::query()->withoutGlobalScopes()->find($this->photoId);

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
