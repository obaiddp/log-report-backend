<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller
{
    public function index(IndexDepartmentRequest $request): AnonymousResourceCollection
    {
        $departments = Department::query()
            ->withCount(['users', 'technicalPersonnel'])
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($request->sortBy(), $request->sortDirection())
            ->orderBy('id', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return DepartmentResource::collection($departments);
    }

    public function show(Department $department): DepartmentResource
    {
        $department->loadCount(['users', 'technicalPersonnel']);

        return new DepartmentResource($department);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::query()->create($request->validated());
        $department->loadCount(['users', 'technicalPersonnel']);

        return DepartmentResource::make($department)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $department->update($request->validated());
        $department->loadCount(['users', 'technicalPersonnel']);

        return new DepartmentResource($department);
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully.']);
    }
}
