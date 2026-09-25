<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexItemTypeRequest;
use App\Http\Requests\StoreItemTypeRequest;
use App\Http\Requests\UpdateItemTypeRequest;
use App\Http\Resources\ItemTypeResource;
use App\Models\ItemType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemTypeController extends Controller
{
    public function index(IndexItemTypeRequest $request): AnonymousResourceCollection
    {
        $itemTypes = ItemType::query()
            ->withCount('supportLogs')
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($request->sortBy(), $request->sortDirection())
            ->orderBy('id', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return ItemTypeResource::collection($itemTypes);
    }

    public function show(ItemType $itemType): ItemTypeResource
    {
        $itemType->loadCount('supportLogs');

        return new ItemTypeResource($itemType);
    }

    public function store(StoreItemTypeRequest $request): JsonResponse
    {
        $itemType = ItemType::query()->create($request->validated());
        $itemType->loadCount('supportLogs');

        return ItemTypeResource::make($itemType)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateItemTypeRequest $request, ItemType $itemType): ItemTypeResource
    {
        $itemType->update($request->validated());
        $itemType->loadCount('supportLogs');

        return new ItemTypeResource($itemType);
    }

    public function destroy(ItemType $itemType): JsonResponse
    {
        $itemType->delete();

        return response()->json(['message' => 'Item type deleted successfully.']);
    }
}
