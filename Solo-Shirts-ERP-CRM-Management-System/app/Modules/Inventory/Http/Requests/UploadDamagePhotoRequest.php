<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class UploadDamagePhotoRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Size is enforced in the service so an oversize image returns 413.
        return [
            'photo' => ['required', 'file', 'mimes:jpeg,png,webp'],
        ];
    }
}
