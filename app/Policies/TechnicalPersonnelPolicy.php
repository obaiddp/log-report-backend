<?php

namespace App\Policies;

use App\Models\TechnicalPersonnel;
use App\Models\User;

class TechnicalPersonnelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TechnicalPersonnel $technicalPersonnel): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TechnicalPersonnel $technicalPersonnel): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TechnicalPersonnel $technicalPersonnel): bool
    {
        return $user->isAdmin();
    }
}
