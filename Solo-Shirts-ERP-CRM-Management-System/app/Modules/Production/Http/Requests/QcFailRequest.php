<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Production\Models\QcInspection;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Phase 7C — fail an item at QC and route it for internal production rework. The
 * failure reason is mandatory; the rework target stage is where the garment goes
 * to be fixed before it re-flows through QC. This is NOT a customer alteration.
 */
final class QcFailRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'failure_reason' => ['required', Rule::in(QcInspection::FAILURE_REASONS)],
            'rework_target_stage' => ['required', Rule::in(QcInspection::REWORK_TARGET_STAGES)],
            'failure_stage' => ['nullable', Rule::in(QcInspection::REWORK_TARGET_STAGES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
