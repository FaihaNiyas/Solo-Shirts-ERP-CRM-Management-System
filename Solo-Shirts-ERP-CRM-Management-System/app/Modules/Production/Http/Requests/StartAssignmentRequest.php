<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class StartAssignmentRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Starting an assignment takes no body; the bundle is identified by the
        // route's assignment id.
        return [];
    }
}
