<?php

namespace App\Http\Middleware;

use App\Enums\RecordStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user !== null && $user->status === RecordStatus::Active,
            403,
            'Your account is inactive.',
        );

        return $next($request);
    }
}
