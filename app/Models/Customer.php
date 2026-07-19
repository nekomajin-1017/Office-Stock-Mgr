<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
  'code',
  'name',
  'postal_code',
  'address',
  'phone',
  'email',
  'contact_person',
  'is_active',
])]
class Customer extends Model
{
  use HasFactory;

  public function sales(): HasMany
  {
    return $this->hasMany(Sale::class);
  }

  protected function casts(): array
  {
    return ['is_active' => 'boolean'];
  }
}
