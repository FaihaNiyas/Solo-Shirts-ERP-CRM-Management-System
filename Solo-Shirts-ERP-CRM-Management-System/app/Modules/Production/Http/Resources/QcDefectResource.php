<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\QcDefect;
use App\Modules\Production\Models\QcDefectPhoto;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * @mixin QcDefect
 */
final class QcDefectResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'defect_category_id' => $this->defect_category_id,
            'severity' => $this->severity,
            'notes' => $this->notes,
            'photos' => $this->photos->map(fn (QcDefectPhoto $photo): array => [
                'id' => $photo->id,
                // Raw storage path is never exposed — only a temporary signed URL.
                'url' => URL::temporarySignedRoute(
                    'qc.photos.download',
                    now()->addMinutes(10),
                    ['photo' => $photo->id],
                ),
                'has_thumbnail' => $photo->thumb_path !== null,
            ])->all(),
        ];
    }
}
