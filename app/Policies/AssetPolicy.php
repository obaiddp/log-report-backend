<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->isAdmin() || $user->isTechnicalResource();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->isAdmin();
    }
}
