<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Shared\Http\Requests\BaseFormRequest;

final class DisableTwoFactorRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Disabling 2FA requires BOTH the current password AND a valid OTP.
        return [
            'password' => ['required', 'string'],
            'otp' => ['required', 'string'],
        ];
    }
}
