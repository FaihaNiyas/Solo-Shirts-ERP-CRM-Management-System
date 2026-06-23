<?php

declare(strict_types=1);

namespace App\Modules\Shared\Http\Requests;

use App\Modules\Shared\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base for every Form Request. Overrides validation failure so callers always
 * receive the standard 422 error envelope with code VALIDATION_FAILED instead
 * of Laravel's default error shape.
 */
abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                message: 'The given data was invalid.',
                code: 'VALIDATION_FAILED',
                errors: $validator->errors()->toArray(),
                status: 422,
            )
        );
    }
}
