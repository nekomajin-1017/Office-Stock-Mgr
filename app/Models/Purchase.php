<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
  'purchase_number',
  'supplier_id',
  'purchase_date',
  'status',
  'subtotal',
  'tax_amount',
  'total_amount',
  'created_by',
  'confirmed_at',
])]
class Purchase extends Model
{
  use HasFactory;

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(Supplier::class);
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function items(): HasMany
  {
    return $this->hasMany(PurchaseItem::class);
  }

  protected function casts(): array
  {
    return [
      'purchase_date' => 'date',
      'subtotal' => 'decimal:2',
      'tax_amount' => 'decimal:2',
      'total_amount' => 'decimal:2',
      'confirmed_at' => 'datetime',
    ];
  }
}
