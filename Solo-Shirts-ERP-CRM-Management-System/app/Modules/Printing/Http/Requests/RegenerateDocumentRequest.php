<?php

declare(strict_types=1);

namespace App\Modules\Printing\Http\Requests;

use App\Modules\Printing\Models\Document;
use App\Modules\Shared\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

final class RegenerateDocumentRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(Document::KINDS)],
            'reference_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
