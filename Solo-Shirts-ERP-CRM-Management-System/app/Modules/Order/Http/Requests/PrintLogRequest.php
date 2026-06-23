<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class PrintLogRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'is_reprint' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
