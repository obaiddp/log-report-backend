<?php

namespace App\Http\Controllers;

use App\Models\AssetInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class AssetInspectionController
{
    private const RELATIONS = [
        'department',
        'itemType',
        'preparedBy',
    ];

    public function getAssetInspections(Request $request): JsonResponse
    {
        $assets = AssetInspection::with(self::RELATIONS)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $assets,
        ]);
    }

    public function postAssetInspections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'cell' => ['nullable', 'string', 'max:20'],
            'department_id' => ['required', 'exists:departments,id'],
            'territory' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            

            'item_type_id' => ['required', 'exists:item_types,id'],
            'model' => ['required', 'string', 'max:255'],
            'brand' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'item_tag' => ['nullable', 'string', 'max:255'],
            'ram' => ['nullable', 'string', 'max:50'],
            'generation' => ['nullable', 'string', 'max:50'],
            'hard_drive' => ['nullable', 'string', 'max:50'],

            'item_problem' => ['nullable', 'string'],
            'it_remarks' => ['nullable', 'string'],
            'admin_remarks' => ['nullable', 'string'],

            'prepared_by' => ['required', 'exists:users,id'],
            'user_signature' => ['nullable', 'string', 'max:255'],
            'admin_signature' => ['nullable', 'string', 'max:255'],
        ]);

        $asset = AssetInspection::create($validated);

        return response()->json([
            'status' => 'success',
            'data'   => $asset,
        ], 201);
    }

    public function getAssetInspectionById(Request $request): JsonResponse
    {
        $asset = AssetInspection::with(self::RELATIONS)->findOrFail($request->id);

        return response()->json([
            'status' => 'success',
            'data'   => $asset,
        ]);
    }

    public function updateAssetInspectionById(Request $request): JsonResponse
    {
        $asset = AssetInspection::findOrFail($request->id);
        $validated = $request->validate([
            'username' => ['string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'cell' => ['nullable', 'string', 'max:20'],
            'department_id' => ['exists:departments,id'],
            'territory' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            

            'item_type_id' => ['exists:item_types,id'],
            'model' => ['string', 'max:255'],
            'brand' => ['string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'item_tag' => ['nullable', 'string', 'max:255'],
            'ram' => ['nullable', 'string', 'max:50'],
            'generation' => ['nullable', 'string', 'max:50'],
            'hard_drive' => ['nullable', 'string', 'max:50'],

            'item_problem' => ['nullable', 'string'],
            'it_remarks' => ['nullable', 'string'],
            'admin_remarks' => ['nullable', 'string'],

            'prepared_by' => ['exists:users,id'],
            'user_signature' => ['nullable', 'string', 'max:255'],
            'admin_signature' => ['nullable', 'string', 'max:255'],
        ]);

        $asset->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Asset inspection updated successfully',
            'data'   => $asset->load(self::RELATIONS),
        ]);
    }

    public function deleteAssetInspectionById(Request $request): JsonResponse
    {
        $asset = AssetInspection::findOrFail($request->id);
        $asset->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Asset inspection deleted successfully',
        ]);
    }
}