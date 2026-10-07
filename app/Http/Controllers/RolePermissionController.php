<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;


class RolePermissionController
{
    public function getRolePermissions(Request $request, $role_id): JsonResponse
    {
        $role = Role::find($role_id);
        if (!$role) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Role not found'
                ],
                404
            );
        }

        $rolePermissions = $role->permissions;
        return response()->json(
            [
                'status' => 'success',
                'data' => $rolePermissions
            ],
            200
        );
    }

    public function updateRolePermissions(Request $request, $role_id): JsonResponse {

        // Validate that permission_ids is sent as an array
        $validated = $request->validate([
            'permission_ids'   => 'present|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::find($role_id);
        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role not found'
            ], 404);
        }

        // Synchronize pivot table to match array of IDs passed from frontend
        $role->permissions()->sync($validated['permission_ids']);

        return response()->json([
            'status' => 'success',
            'message' => 'Role permissions updated successfully',
            'data' => [
                'role' => $role,
                'permission' => $role->permissions()->get()
            ]
        ], 201);
    }
}