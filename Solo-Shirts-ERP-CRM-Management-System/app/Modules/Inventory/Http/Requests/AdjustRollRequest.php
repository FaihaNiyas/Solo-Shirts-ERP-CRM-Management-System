<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Models\FabricMovement;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class AdjustRollRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isAdjustOut = $this->input('type') === FabricMovement::TYPE_ADJUST_OUT;

        return [
            'type' => ['required', Rule::in([FabricMovement::TYPE_ADJUST_IN, FabricMovement::TYPE_ADJUST_OUT])],
            'metres' => ['required', 'numeric', 'min:0.01'],
            // Writing stock down demands a substantive reason.
            'reason' => $isAdjustOut
                ? ['required', 'string', 'min:10', 'max:255']
                : ['nullable', 'string', 'max:255'],
        ];
    }
}
