<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController
{
    // get all roles
    public function getRoles(Request $request): JsonResponse
    {
        $presentRole = Role::all();

        return response()->json(
            [
                'status' => 'success',
                'data' => $presentRole
            ],
            200
        );
    }

    // post 1 Role
    public function postRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $role = Role::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $role
        ], 201);
    }
    
}
