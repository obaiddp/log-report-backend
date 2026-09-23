<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = array_map(
            static fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user !== null && in_array($user->role, $allowedRoles, true),
            403,
            'You do not have permission to perform this action.',
        );

        return $next($request);
    }
}
