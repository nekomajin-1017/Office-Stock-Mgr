<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
class Supplier extends Model
{
  use HasFactory;

  public function purchases(): HasMany
  {
    return $this->hasMany(Purchase::class);
  }

  public function scopeActive(Builder $query): void
  {
    $query->where('is_active', true);
  }

  protected function casts(): array
  {
    return ['is_active' => 'boolean'];
  }
}
