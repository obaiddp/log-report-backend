<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user){
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $hasPermission = $user->role ? $user->role->permissions()->where('permissions.name', $permission)->exists() : false;

        if (!$hasPermission){
            return response()->json(['message' => 'Donot have permission'], 403);
        }
        
        return $next($request);
    }
}