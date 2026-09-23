<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexTechnicalPersonnelRequest;
use App\Http\Requests\StoreTechnicalPersonnelRequest;
use App\Http\Requests\UpdateTechnicalPersonnelRequest;
use App\Http\Resources\TechnicalPersonnelResource;
use App\Models\TechnicalPersonnel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TechnicalPersonnelController extends Controller
{
    public function index(IndexTechnicalPersonnelRequest $request): AnonymousResourceCollection
    {
        $personnel = TechnicalPersonnel::query()
            ->with('department')
            ->withCount('inspections')
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%");
                });
            })
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($request->sortBy(), $request->sortDirection())
            ->orderBy('id', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return TechnicalPersonnelResource::collection($personnel);
    }

    public function show(TechnicalPersonnel $technicalPersonnel): TechnicalPersonnelResource
    {
        $technicalPersonnel->load('department')->loadCount('inspections');

        return new TechnicalPersonnelResource($technicalPersonnel);
    }

    public function store(StoreTechnicalPersonnelRequest $request): JsonResponse
    {
        $personnel = TechnicalPersonnel::query()->create($request->validated());
        $personnel->load('department')->loadCount('inspections');

        return TechnicalPersonnelResource::make($personnel)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTechnicalPersonnelRequest $request, TechnicalPersonnel $technicalPersonnel): TechnicalPersonnelResource
    {
        $technicalPersonnel->update($request->validated());
        $technicalPersonnel->load('department')->loadCount('inspections');

        return new TechnicalPersonnelResource($technicalPersonnel);
    }

    public function destroy(TechnicalPersonnel $technicalPersonnel): JsonResponse
    {
        $technicalPersonnel->delete();

        return response()->json(['message' => 'Technical personnel deleted successfully.']);
    }
}
