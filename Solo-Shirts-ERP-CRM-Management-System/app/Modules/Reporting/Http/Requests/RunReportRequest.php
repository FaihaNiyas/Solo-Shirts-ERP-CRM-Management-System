<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Requests;

use App\Modules\Reporting\Services\ReportRunner;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class RunReportRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Strictly whitelist the kind against the registered reports so no
            // arbitrary handler can be invoked via params.
            'kind' => ['required', 'string', Rule::in(app(ReportRunner::class)->kinds())],
            'params' => ['sometimes', 'array'],
        ];
    }
}
