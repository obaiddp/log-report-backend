<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\IssueTypeResource;
use App\Http\Resources\ItemTypeResource;
use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\IssueType;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportLogLookupController extends Controller
{
    /**
     * Read-only options needed by support-log forms. Technical resources receive
     * active options; administrators also receive inactive historical options.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isTechnicalResource()), 403);

        $departments = Department::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('status', RecordStatus::Active->value))
            ->orderBy('name')
            ->get();
        $issueTypes = IssueType::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('status', RecordStatus::Active->value))
            ->orderBy('name')
            ->get();
        $itemTypes = ItemType::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('status', RecordStatus::Active->value))
            ->orderBy('name')
            ->get();
        $resources = User::query()
            ->with('department')
            ->where('status', RecordStatus::Active->value)
            ->orderBy('name')
            ->get()
            ->filter(static fn (User $user): bool => $user->isTechnicalResource() || $user->isAdmin())
            ->values();

        return response()->json([
            'departments' => DepartmentResource::collection($departments),
            'issue_types' => IssueTypeResource::collection($issueTypes),
            'item_types' => ItemTypeResource::collection($itemTypes),
            'technical_resources' => UserResource::collection($resources),
        ]);
    }
}
