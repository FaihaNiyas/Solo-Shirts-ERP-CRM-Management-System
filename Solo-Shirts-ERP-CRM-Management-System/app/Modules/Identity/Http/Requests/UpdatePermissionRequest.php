<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

final class UpdatePermissionRequest extends BaseFormRequest
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
        /** @var Permission $permission */
        $permission = $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_.]+$/', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ];
    }
}
