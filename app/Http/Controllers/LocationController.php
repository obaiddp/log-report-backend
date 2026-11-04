<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;


class LocationController
{
    // get all locations
    public function getLocations(Request $request): JsonResponse
    {
        $presentLocation = Location::all();

        return response()->json(
            [
                'status' => 'success',
                'data' => $presentLocation
            ],
            200
        );
    }

    // post 1 locations
    public function postLocations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_head_office' => 'required|boolean',
            'in_use' => 'required|boolean'
        ]);

        $location = Location::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $location
        ], 201);
    }

    // get location by id
    public function getLocationById(Request $request): JsonResponse
    {
        $location = Location::find($request->id);

        if (!$location) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Location not found'
                ],
                404
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $location
            ],
            200
        );
    }

    // update location by id
    public function updateLocationById(Request $request): JsonResponse
    {
        $location = Location::find($request->id);

        if (!$location) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Location not found'
                ],
                404
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_head_office' => 'required|boolean',
            'in_use' => 'required|boolean'
        ]);

        $location->update($validated);

        return response()->json(
            [
                'status' => 'success',
                'data' => $location
            ],
            200
        );
    }

    // delete location by id
    public function deleteLocationById(Request $request): JsonResponse
    {
        try {
            // findOrFail: handles 404
            $location = Location::findOrFail($request->id);
            $location->delete();

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Location deleted successfully'
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
                    'message' => 'Cannot delete location: it is referenced by existing support logs',
                ], 409);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete location',
            ], 500);
        }
    }
}
