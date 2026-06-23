<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Exceptions\AuthException;
use App\Modules\Identity\Models\LoginAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

final class AuthService
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    /**
     * @return array{token: string, user: User, abilities: list<string>}
     */
    public function login(string $email, string $password, ?string $otp, string $ip, ?string $userAgent): array
    {
        if ($this->isLockedOut($email, $ip)) {
            $this->record($email, $ip, $userAgent, false);
            throw AuthException::lockedOut();
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || !Hash::check($password, $user->password)) {
            $this->record($email, $ip, $userAgent, false);
            throw AuthException::invalidCredentials();
        }

        if (!$user->is_active) {
            $this->record($email, $ip, $userAgent, false);
            throw AuthException::accountInactive();
        }

        $user->loadMissing('branch');

        if ($user->branch !== null && !$user->branch->is_active) {
            $this->record($email, $ip, $userAgent, false);
            throw AuthException::branchInactive();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);

        $this->enforceTwoFactor($user, $otp, $email, $ip, $userAgent);

        $this->record($email, $ip, $userAgent, true);

        $abilities = $this->abilitiesFor($user);
        $token = $user->createToken('api', $abilities)->plainTextToken;

        return ['token' => $token, 'user' => $user, 'abilities' => $abilities];
    }

    /**
     * Issue a fresh token and revoke the current one in a single transaction.
     *
     * @return array{token: string, abilities: list<string>}
     */
    public function refresh(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $user->currentAccessToken()->delete();

            app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);
            $abilities = $this->abilitiesFor($user);

            return [
                'token' => $user->createToken('api', $abilities)->plainTextToken,
                'abilities' => $abilities,
            ];
        });
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * @return list<string>
     */
    public function abilitiesFor(User $user): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);

        if ($user->hasRole('Owner')) {
            return ['*'];
        }

        return $user->getAllPermissions()->pluck('name')->values()->all();
    }

    private function enforceTwoFactor(User $user, ?string $otp, string $email, string $ip, ?string $userAgent): void
    {
        if ($user->hasTwoFactorEnabled()) {
            if ($otp === null || $otp === '') {
                $this->record($email, $ip, $userAgent, false);
                throw AuthException::twoFactorRequired();
            }

            if (!$this->twoFactor->verify($user, $otp)) {
                $this->record($email, $ip, $userAgent, false);
                throw AuthException::invalidOtp();
            }

            return;
        }

        if ($this->roleRequiresTwoFactor($user)) {
            $this->record($email, $ip, $userAgent, false);
            throw AuthException::twoFactorSetupRequired();
        }
    }

    private function roleRequiresTwoFactor(User $user): bool
    {
        /** @var list<string> $required */
        $required = config('identity.two_factor_required_roles', []);

        return $user->getRoleNames()->intersect($required)->isNotEmpty();
    }

    private function isLockedOut(string $email, string $ip): bool
    {
        $max = (int) config('identity.login_max_attempts', 5);
        $window = now()->subMinutes((int) config('identity.login_decay_minutes', 15));

        $lastSuccessAt = LoginAttempt::query()
            ->where('email', $email)
            ->where('ip', $ip)
            ->where('success', true)
            ->where('attempted_at', '>=', $window)
            ->max('attempted_at');

        $since = $lastSuccessAt ?? $window;

        $failures = LoginAttempt::query()
            ->where('email', $email)
            ->where('ip', $ip)
            ->where('success', false)
            ->where('attempted_at', '>', $since)
            ->count();

        return $failures >= $max;
    }

    private function record(string $email, string $ip, ?string $userAgent, bool $success): void
    {
        LoginAttempt::query()->create([
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'success' => $success,
            'attempted_at' => now(),
        ]);
    }
}
