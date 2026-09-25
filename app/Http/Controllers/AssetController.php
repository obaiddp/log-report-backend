<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexAssetRequest;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AssetController extends Controller
{
    public function index(IndexAssetRequest $request): AnonymousResourceCollection
    {
        $assets = Asset::query()
            ->with(['user.department', 'latestInspection'])
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('asset_tag', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('ram', 'like', "%{$search}%")
                        ->orWhere('storage', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->validated('type'), fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->whereHas(
                'user',
                fn (Builder $userQuery) => $userQuery->where('department_id', $departmentId),
            ))
            ->when($request->validated('user_id'), fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->whereHas(
                'user',
                fn (Builder $userQuery) => $userQuery->where('status', $status),
            ))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('acquired_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('acquired_at', '<=', $request->input('date_to')))
            ->orderBy($request->sortBy(), $request->sortDirection())
            ->orderBy('id', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return AssetResource::collection($assets);
    }

    public function show(Asset $asset): AssetResource
    {
        $asset->load(['user.department', 'latestInspection']);

        return new AssetResource($asset);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = Asset::query()->create($request->validated());
        $asset->load(['user.department', 'latestInspection']);

        return AssetResource::make($asset)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): AssetResource
    {
        $asset->update($request->validated());
        $asset->load(['user.department', 'latestInspection']);

        return new AssetResource($asset);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        Gate::forUser(request()->user())->authorize('delete', $asset);
        $asset->delete();

        return response()->json(['message' => 'Asset deleted successfully.']);
    }
}
