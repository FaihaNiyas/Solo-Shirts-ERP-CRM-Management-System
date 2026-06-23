<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Requests;

use App\Models\User;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class CreateSlotRequest extends BaseFormRequest
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
            'slot_code' => [
                'required', 'string', 'max:50',
                Rule::unique('rack_slots', 'slot_code')->where(fn ($q) => $q->where('branch_id', $branchId)),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
