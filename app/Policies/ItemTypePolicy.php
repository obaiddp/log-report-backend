<?php

namespace App\Policies;

use App\Models\ItemType;
use App\Models\User;

class ItemTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ItemType $itemType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ItemType $itemType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ItemType $itemType): bool
    {
        return $user->isAdmin();
    }
}
