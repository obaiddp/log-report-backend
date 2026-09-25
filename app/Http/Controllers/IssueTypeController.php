<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexIssueTypeRequest;
use App\Http\Requests\StoreIssueTypeRequest;
use App\Http\Requests\UpdateIssueTypeRequest;
use App\Http\Resources\IssueTypeResource;
use App\Models\IssueType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueTypeController extends Controller
{
    public function index(IndexIssueTypeRequest $request): AnonymousResourceCollection
    {
        $issueTypes = IssueType::query()
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

        return IssueTypeResource::collection($issueTypes);
    }

    public function show(IssueType $issueType): IssueTypeResource
    {
        $issueType->loadCount('supportLogs');

        return new IssueTypeResource($issueType);
    }

    public function store(StoreIssueTypeRequest $request): JsonResponse
    {
        $issueType = IssueType::query()->create($request->validated());
        $issueType->loadCount('supportLogs');

        return IssueTypeResource::make($issueType)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateIssueTypeRequest $request, IssueType $issueType): IssueTypeResource
    {
        $issueType->update($request->validated());
        $issueType->loadCount('supportLogs');

        return new IssueTypeResource($issueType);
    }

    public function destroy(IssueType $issueType): JsonResponse
    {
        $issueType->delete();

        return response()->json(['message' => 'Issue type deleted successfully.']);
    }
}
