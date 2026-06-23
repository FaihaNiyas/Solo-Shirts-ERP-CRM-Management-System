<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Resources;

use App\Modules\Production\Models\DefectCategory;
use App\Modules\Shared\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin DefectCategory
 */
final class DefectCategoryResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
