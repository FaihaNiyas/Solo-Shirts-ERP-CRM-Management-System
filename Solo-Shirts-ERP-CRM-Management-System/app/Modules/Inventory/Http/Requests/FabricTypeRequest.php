<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Models\FabricType;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class FabricTypeRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var FabricType|null $type */
        $type = $this->route('fabricType');
        $ignoreId = $type?->id;

        return [
            'code' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string', 'max:50',
                Rule::unique('fabric_types', 'code')->ignore($ignoreId),
            ],
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:100'],
            'low_stock_threshold_metres' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
