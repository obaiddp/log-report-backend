<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Http\Requests\LoginRequest;

use Illuminate\Support\Facades\Hash;


class AuthController
{
    public function login(Request $request)
    {
        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json(['message' => 'Too many login attempts. Please try again later.'], 429);
        }

        $user = User::where('email', $request->input('email'))->first();

        // 1. Password verification and user existence check using Hash::check
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // 2. Email verification check
        // if (is_null($user->email_verified_at)) {
        //     RateLimiter::hit($throttleKey, 60);
        //     abort(403, 'Account is unverified.');
        // }
        // RateLimiter::clear($throttleKey);

        // 3. Session and Auth login
        $request->session()->regenerate();
        Auth::login($user, $request->boolean('remember'));

        return response()->json([
            'data' => $user->load('role.permissions'),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'has_session' => $request->hasSession(),
            'session_id' => $request->session()->getId(),
            'auth_check' => Auth::check(),
            'user_from_request' => $request->user()->load('role.permissions'),
            'auth_user' => Auth::user()->load('role.permissions'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
