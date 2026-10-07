<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;

class PermissionController
{
    // get all permissions
    public function getPermissions(Request $request): JsonResponse
    {
        $presentPermissions = Permission::all();

        return response()->json(
            [
                'status' => 'success',
                'data' => $presentPermissions
            ],
            200
        );
    }

    // post 1 permission
    public function postPermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $permission = Permission::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $permission   
        ], 201);
    }

    // delete 1 permission
    public function deletePermissions(Request $request, $id): JsonResponse
    {
        logger("Deleting permission with ID: " . $id);
        $permission = Permission::findOrFail($id);
        logger("Found permission with ID: " . $permission);
        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission not found'
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission deleted'
        ], 200);
    }
}
