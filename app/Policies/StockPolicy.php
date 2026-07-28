<?php

namespace App\Policies;

use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }
}
