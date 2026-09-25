<?php

namespace App\Policies;

use App\Models\SupportLog;
use App\Models\User;

class SupportLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function view(User $user, SupportLog $supportLog): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function update(User $user, SupportLog $supportLog): bool
    {
        return $user->isAdmin()
            || ($user->isTechnicalResource() && $supportLog->isAssignedTo($user));
    }

    public function delete(User $user, SupportLog $supportLog): bool
    {
        return $user->isAdmin();
    }

    public function assign(User $user, SupportLog $supportLog): bool
    {
        return $user->isAdmin();
    }
}
