<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemType;
use Illuminate\Http\JsonResponse;

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
        logger("----------- Does the item get created? -----------");
        logger($item);

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
}
