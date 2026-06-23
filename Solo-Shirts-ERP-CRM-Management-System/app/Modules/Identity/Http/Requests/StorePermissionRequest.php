<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class StorePermissionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('permissions.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Convention: lowercase dotted keys, e.g. "module.action".
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_.]+$/', 'unique:permissions,name'],
        ];
    }
}
