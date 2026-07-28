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

  public function view(User $user, Purchase $purchase): bool
  {
    return $user->is_active;
  }

  public function confirm(User $user, Purchase $purchase): bool
  {
    return $user->is_active;
  }
}
