<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Resources;

use App\Modules\Printing\Http\Resources\DocumentResource;
use App\Modules\Reporting\Models\ReportJob;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin ReportJob
 */
final class ReportJobResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'status' => $this->status,
            'document_id' => $this->document_id,
            'error' => $this->error,
            'requested_at' => $this->date($this->requested_at),
            'completed_at' => $this->date($this->completed_at),
            'document' => $this->whenLoaded('document', fn () => new DocumentResource($this->document)),
        ];
    }
}
