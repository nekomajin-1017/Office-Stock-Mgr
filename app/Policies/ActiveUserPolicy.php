<?php

namespace App\Policies;

use App\Models\User;

abstract class ActiveUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user): bool
    {
        return $user->is_active;
    }
}
