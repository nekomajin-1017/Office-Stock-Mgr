<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
  'sale_id',
  'product_id',
  'quantity',
  'unit_price',
  'cost_unit_price',
  'subtotal',
  'cost_amount',
  'tax_amount',
])]
class SaleItem extends Model
{
  use HasFactory;

  public function sale(): BelongsTo
  {
    return $this->belongsTo(Sale::class);
  }

  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }

  protected function casts(): array
  {
    return [
      'quantity' => 'integer',
      'unit_price' => 'decimal:2',
      'cost_unit_price' => 'decimal:2',
      'subtotal' => 'decimal:2',
      'cost_amount' => 'decimal:2',
      'tax_amount' => 'decimal:2',
    ];
  }
}
