<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->is_active && $purchase->isDraft();
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->is_active && $purchase->isDraft();
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->is_active;
    }

    public function confirm(User $user, Purchase $purchase): bool
    {
        return $user->is_active;
    }

    public function cancel(User $user, Purchase $purchase): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
