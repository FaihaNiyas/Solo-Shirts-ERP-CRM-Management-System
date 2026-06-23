<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Requests;

use App\Modules\Measurement\Http\Requests\Concerns\ValidatesMeasurementData;
use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class CreateVersionRequest extends BaseFormRequest
{
    use ValidatesMeasurementData;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->measurementRules();
    }
}
