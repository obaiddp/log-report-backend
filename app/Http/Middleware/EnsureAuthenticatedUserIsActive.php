<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedUserIsActive
{
    /**
     * Require an active, verified account for an already-authenticated API
     * session. This choice is intentional: seeded and administrator-created
     * accounts are verified, and inactive sessions are revoked immediately.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->isActive()) {
            return response()->json([
                'message' => 'This account is inactive.',
                'error' => 'account_inactive',
            ], 403);
        }

        if (! $user->isVerified()) {
            return response()->json([
                'message' => 'Email verification is required before using the API.',
                'error' => 'email_not_verified',
            ], 403);
        }

        return $next($request);
    }
}
