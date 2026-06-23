<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use App\Models\User;
use App\Modules\Shared\Services\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * After authentication, pins the Spatie permissions team to the user's home
 * branch (so role/permission checks resolve), and seeds BranchContext with any
 * branch the Owner has switched into on this token.
 */
final class ResolveBranchContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->branch_id);

            // Global `api` middleware (SubstituteBindings) runs before this route
            // middleware, so a bound model may have already caused the user's
            // roles/permissions to load under the default (null) team — caching
            // them empty. Drop those relations so they reload under the team we
            // just pinned; otherwise every policy check on a bound route fails.
            $user->unsetRelation('roles')->unsetRelation('permissions');

            $activeBranch = $user->currentAccessToken()->getAttribute('active_branch_id');

            if ($activeBranch !== null) {
                app(BranchContext::class)->setCurrent((int) $activeBranch);
            }
        }

        return $next($request);
    }
}
