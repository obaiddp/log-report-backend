<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController
{
    // get all roles
    public function roles(Request $request): JsonResponse
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

    
}
