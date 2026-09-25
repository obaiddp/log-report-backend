<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function view(User $user, Inspection $inspection): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function delete(User $user, Inspection $inspection): bool
    {
        return $user->isAdmin();
    }
}
