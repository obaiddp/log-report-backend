<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class DepartmentController
{
    // get all departments
    public function getDepartments(Request $request): JsonResponse
    {
        $presentDepartment = Department::all();

        logger("Retrieved all departments");
        logger("Departments: " . $presentDepartment->toJson());

        return response()->json(
            [
                'status' => 'success',
                'data' => $presentDepartment
            ],
            200
        );
    }

    // post 1 departments
    public function postDepartments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255'
        ]);

        $department = Department::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $department
        ], 201);
    }

    // get department by id
    public function getDepartmentById(Request $request): JsonResponse
    {
        $department = Department::find($request->id);

        if (!$department) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Department not found'
                ],
                404
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $department
            ],
            200
        );
    }

    // update department by id
    public function updateDepartmentById(Request $request): JsonResponse
    {
        $department = Department::find($request->id);

        logger("Updating department with ID: " . $request->id);

        if (!$department) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Department not found'
                ],
                404
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $department->update($validated);

        return response()->json(
            [
                'status' => 'success',
                'data' => $department
            ],
            200
        );
    }

    // delete department by id
    public function deleteDepartmentById(Request $request): JsonResponse
    {
        try {
            // findOrFail: handles 404
            $department = Department::findOrFail($request->id);
            $department->delete();
            
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Department deleted successfully'
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
                    'message' => 'Cannot delete department: it is referenced by existing support logs',
                ], 409);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete department',
            ], 500);
        }
    }
}