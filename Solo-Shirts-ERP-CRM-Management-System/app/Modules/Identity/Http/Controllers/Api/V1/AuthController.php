<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Requests\SwitchBranchRequest;
use App\Modules\Identity\Http\Resources\LoginResource;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Modules\Identity\Services\AuthService;
use App\Modules\Identity\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly BranchService $branches,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            email: (string) $request->string('email'),
            password: (string) $request->string('password'),
            otp: $request->filled('otp') ? (string) $request->string('otp') : null,
            ip: (string) $request->ip(),
            userAgent: $request->userAgent(),
        );

        return $this->respond((new LoginResource($result))->toArray($request), 'Logged in');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($this->user($request));

        return $this->respond(null, 'Logged out');
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->auth->refresh($this->user($request));

        return $this->respond([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'abilities' => $result['abilities'],
        ], 'Token refreshed');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $user->loadMissing('branch');

        return $this->respond([
            'user' => (new UserResource($user))->toArray($request),
            'abilities' => $this->auth->abilitiesFor($user),
        ]);
    }

    public function switchBranch(SwitchBranchRequest $request): JsonResponse
    {
        $user = $this->user($request);

        abort_unless($user->hasRole('Owner'), 403);

        $this->branches->switchBranch($user, (int) $request->integer('branch_id'));

        return $this->respond(['active_branch_id' => (int) $request->integer('branch_id')], 'Branch context switched');
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
