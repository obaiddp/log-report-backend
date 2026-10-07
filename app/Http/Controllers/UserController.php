<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class UserController
{
    // get all Users
    public function getUsers(Request $request)
    {
        $currentUsers = User::with('role')->get();
        return response()->json(
            [
                'status' => 'success',
                'data' => $currentUsers
            ],
            200
        );
    }

    // post 1 Users
    public function postUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'designation' => 'required|string|max:255',
            'role_id' => 'required|integer|exists:roles,id'
        ]);

        $newUser = User::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $newUser
        ], 201);
    }

    // get user by id
    public function getUserById(Request $request): JsonResponse
    {
        $user = User::with('role')->find($request->id);

        if (!$user) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'User not found'
                ],
                404
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $user
            ],
            200
        );
    }

    // update user by id
    public function updateUserById(Request $request): JsonResponse
    {
        $user = User::find($request->id);

        if (!$user) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'User not found'
                ],
                404
            );
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'password' => 'string|min:6',
            'designation' => 'string|max:255',
            'role_id' => 'integer|exists:roles,id'
        ]);

        $user->update($validated);

        return response()->json(
            [
                'status' => 'success',
                'data' => $user
            ],
            200
        );
    }

    // delete user by id
    public function deleteUserById(Request $request): JsonResponse
    {
        try {
            // findOrFail: handles 404
            $user = User::findOrFail($request->id);
            $user->delete();
            
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'User deleted successfully'
                ],
                200
            );
        }
        catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? '';

            // 23503 = foreign_key_violation, 23001 = restrict_violation (PostgreSQL)
            // 23000 = generic integrity violation (MySQL)
            if (in_array($sqlState, ['23503', '23001', '23000'], true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cannot delete user: it is referenced by existing support logs',
                ], 409);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete user',
            ], 500);
        }
    }
}