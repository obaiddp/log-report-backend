<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemType;
use Illuminate\Http\JsonResponse;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ItemController
{
    // get all items
    public function getItems(Request $request): JsonResponse
    {
        $presentItems = ItemType::all();

        return response()->json(
            [
                'status' => 'success',
                'data' => $presentItems
            ],
            200
        );
    }

    // post 1 item
    public function postItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $item = ItemType::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $item
        ], 201);
    }

    // get item by id
    public function getItemById(Request $request): JsonResponse
    {  
        $item = ItemType::find($request->id);

        if (!$item) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Item not found'
                ],
                404
            );
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $item
            ],
            200
        );
    }

     // update item by id
    public function updateItemById(Request $request): JsonResponse
    {
        $item = ItemType::find($request->id);

        if (!$item) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Item not found'
                ],
                404
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $item->update($validated);

        return response()->json(
            [
                'status' => 'success',
                'data' => $item
            ],
            200
        );
    }



    // delete item by id
    public function deleteItemById(Request $request): JsonResponse
    {
       

        try {
             // findOrFail: handles 404
            $item = ItemType::findOrFail($request->id);
            $item->delete();

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Item deleted successfully'
                ],
                200
            );
        }
        catch (QueryException $e) {
            logger("DID I reach item deleteion controller");

            $sqlState = $e->errorInfo[0] ?? '';

            // 23503 = foreign_key_violation, 23001 = restrict_violation (PostgreSQL)
            // 23000 = generic integrity violation (MySQL)
            if (in_array($sqlState, ['23503', '23001', '23000'], true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cannot delete item: it is referenced by existing support logs',
                ], 409);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete item',
            ], 500);
        }
    }
}
