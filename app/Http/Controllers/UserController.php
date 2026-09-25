<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('department')
            ->withCount('assets')
            ->when($request->searchTerm(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('territory', 'like', "%{$search}%");
                });
            })
            ->when($request->validated('department_id'), fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->validated('role'), fn (Builder $query, string $role) => $query->where('role', $role))
            ->orderBy($request->sortBy(), $request->sortDirection())
            ->orderBy('id', $request->sortDirection())
            ->paginate($request->perPage())
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        $user->load('department')->loadCount('assets');

        return new UserResource($user);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $attributes = $request->validated();
        unset($attributes['password_confirmation']);
        $attributes['password'] = Hash::make($attributes['password']);

        $user = User::query()->create($attributes);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->load('department')->loadCount('assets');

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $attributes = $request->validated();
        unset($attributes['password_confirmation']);

        if (array_key_exists('password', $attributes)) {
            $attributes['password'] = Hash::make($attributes['password']);
        }

        $user->update($attributes);
        $user->load('department')->loadCount('assets');

        return new UserResource($user);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::forUser(request()->user())->authorize('delete', $user);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
