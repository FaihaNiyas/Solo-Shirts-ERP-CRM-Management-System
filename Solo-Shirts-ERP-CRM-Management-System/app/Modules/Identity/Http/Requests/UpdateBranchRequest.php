<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Models\Branch;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class UpdateBranchRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');
        $branchId = $branch?->getKey();

        return [
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branchId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'gst_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
