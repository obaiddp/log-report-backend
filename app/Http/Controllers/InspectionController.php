<?php

namespace App\Http\Controllers;

use App\Enums\InspectionCategory;
use App\Http\Requests\IndexInspectionRequest;
use App\Http\Requests\StoreInspectionRequest;
use App\Http\Requests\UpdateInspectionRequest;
use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InspectionController extends Controller
{
    public function index(IndexInspectionRequest $request): AnonymousResourceCollection
    {
        $inspections = Inspection::query()
            ->with(['asset.user', 'technicalPersonnel', 'createdBy'])
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('problem_id', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('asset', fn (Builder $assetQuery) => $assetQuery
                            ->where('asset_tag', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%")
                            ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")))
                        ->orWhereHas('technicalPersonnel', fn (Builder $personnelQuery) => $personnelQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->validated('type'), fn (Builder $query, string $type) => $query->whereHas(
                'asset',
                fn (Builder $assetQuery) => $assetQuery->where('type', $type),
            ))
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->whereHas(
                'asset.user',
                fn (Builder $userQuery) => $userQuery->where('department_id', $departmentId),
            ))
            ->when($request->validated('user_id'), fn (Builder $query, int $userId) => $query->whereHas(
                'asset',
                fn (Builder $assetQuery) => $assetQuery->where('user_id', $userId),
            ))
            ->when($request->validated('category'), fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($request->validated('technical_personnel_id'), fn (Builder $query, int $personnelId) => $query->where('technical_personnel_id', $personnelId))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('inspection_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('inspection_date', '<=', $request->input('date_to')))
            ->orderBy($request->sortBy(), $request->sortDirection())
            ->orderBy('id', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return InspectionResource::collection($inspections);
    }

    public function show(Inspection $inspection): InspectionResource
    {
        $inspection->load(['asset.user', 'technicalPersonnel', 'createdBy']);

        return new InspectionResource($inspection);
    }

    public function store(StoreInspectionRequest $request): JsonResponse
    {
        $inspection = Inspection::query()->create($request->validated());
        $inspection->load(['asset.user', 'technicalPersonnel', 'createdBy']);

        return InspectionResource::make($inspection)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateInspectionRequest $request, Inspection $inspection): InspectionResource
    {
        $attributes = $request->validated();

        if (
            ($attributes['category'] ?? null) === InspectionCategory::NewPurchase->value
            && ! array_key_exists('sub_category', $attributes)
        ) {
            $attributes['sub_category'] = null;
        }

        $inspection->update($attributes);
        $inspection->load(['asset.user', 'technicalPersonnel', 'createdBy']);

        return new InspectionResource($inspection);
    }

    public function destroy(Inspection $inspection): JsonResponse
    {
        $inspection->delete();

        return response()->json(['message' => 'Inspection deleted successfully.']);
    }
}
