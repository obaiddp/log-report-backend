<?php

namespace App\Policies;

use App\Models\IssueType;
use App\Models\User;

class IssueTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, IssueType $issueType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, IssueType $issueType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, IssueType $issueType): bool
    {
        return $user->isAdmin();
    }
}
