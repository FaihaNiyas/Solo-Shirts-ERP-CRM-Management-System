<?php

declare(strict_types=1);

namespace App\Modules\Printing\Http\Resources;

use App\Modules\Printing\Models\Document;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Serializes a document with a fresh 10-minute signed download URL. The raw disk
 * path is never exposed — the signed URL is the only way to fetch the bytes.
 *
 * @mixin Document
 */
final class DocumentResource extends BaseResource
{
    public const URL_TTL_MINUTES = 10;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'content_hash' => $this->content_hash,
            'size_bytes' => $this->size_bytes,
            'generated_at' => $this->date($this->generated_at),
            'download_url' => URL::temporarySignedRoute(
                'documents.download',
                now()->addMinutes(self::URL_TTL_MINUTES),
                ['document' => $this->id],
            ),
        ];
    }
}
