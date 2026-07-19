<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
  'sale_number',
  'customer_id',
  'sale_date',
  'status',
  'subtotal',
  'tax_amount',
  'total_amount',
  'created_by',
  'confirmed_at',
])]
class Sale extends Model
{
  use HasFactory;

  public function customer(): BelongsTo
  {
    return $this->belongsTo(Customer::class);
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function items(): HasMany
  {
    return $this->hasMany(SaleItem::class);
  }

  protected function casts(): array
  {
    return [
      'sale_date' => 'date',
      'subtotal' => 'decimal:2',
      'tax_amount' => 'decimal:2',
      'total_amount' => 'decimal:2',
      'confirmed_at' => 'datetime',
    ];
  }
}
