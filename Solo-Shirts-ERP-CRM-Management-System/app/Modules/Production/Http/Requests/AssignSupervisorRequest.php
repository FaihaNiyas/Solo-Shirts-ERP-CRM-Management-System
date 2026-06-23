<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Production\Models\ProductionStageSupervisor;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class AssignSupervisorRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'stage' => ['required', 'string', Rule::in(ProductionStageSupervisor::SECTIONS)],
        ];
    }
}
