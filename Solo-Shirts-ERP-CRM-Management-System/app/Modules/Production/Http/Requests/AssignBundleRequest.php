<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class AssignBundleRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bundle_id' => ['required', 'integer', 'exists:cut_bundles,id'],
            'tailor_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
