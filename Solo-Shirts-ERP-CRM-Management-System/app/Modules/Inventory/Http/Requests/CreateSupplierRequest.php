<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Models\User;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class CreateSupplierRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->user();
        $branchId = $user?->branch_id;

        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('suppliers', 'code')->where(fn ($q) => $q->where('branch_id', $branchId)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
