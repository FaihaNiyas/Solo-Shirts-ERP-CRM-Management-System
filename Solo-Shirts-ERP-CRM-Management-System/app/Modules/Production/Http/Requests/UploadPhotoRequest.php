<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class UploadPhotoRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Size is checked in the service so an oversize image returns 413 rather
        // than a generic 422; here we only gate the file type.
        return [
            'photo' => ['required', 'file', 'mimes:jpeg,png,webp'],
        ];
    }
}
