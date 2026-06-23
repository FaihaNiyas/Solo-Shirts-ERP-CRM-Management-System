<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Identity\Http\Requests\ConfirmTwoFactorRequest;
use App\Modules\Identity\Http\Requests\DisableTwoFactorRequest;
use App\Modules\Identity\Services\TwoFactorService;
use App\Modules\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class TwoFactorController extends BaseApiController
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function enable(Request $request): JsonResponse
    {
        $data = $this->twoFactor->enable($this->user($request));

        return $this->respond($data, 'Two-factor secret generated');
    }

    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        if (!$this->twoFactor->confirm($this->user($request), (string) $request->string('otp'))) {
            return ApiResponse::error('The one-time passcode is invalid.', 'INVALID_OTP', status: 401);
        }

        return $this->respond(null, 'Two-factor authentication enabled');
    }

    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $user = $this->user($request);

        if (!Hash::check((string) $request->string('password'), $user->password)) {
            return ApiResponse::error('The password is incorrect.', 'INVALID_CREDENTIALS', status: 401);
        }

        if (!$this->twoFactor->verify($user, (string) $request->string('otp'))) {
            return ApiResponse::error('The one-time passcode is invalid.', 'INVALID_OTP', status: 401);
        }

        $this->twoFactor->disable($user);

        return $this->respond(null, 'Two-factor authentication disabled');
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
