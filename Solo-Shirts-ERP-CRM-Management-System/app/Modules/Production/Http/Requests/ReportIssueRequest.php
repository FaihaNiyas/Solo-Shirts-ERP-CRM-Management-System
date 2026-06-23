<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Production\Models\ProductionIssue;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class ReportIssueRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'issue_type' => ['required', 'string', Rule::in(ProductionIssue::TYPES)],
            // A description is mandatory so every issue carries the "what".
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
