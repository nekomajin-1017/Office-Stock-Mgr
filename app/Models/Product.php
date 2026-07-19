<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
  'category_id',
  'code',
  'name',
  'unit',
  'standard_sale_price',
  'reorder_level',
  'description',
  'is_active',
])]
class Product extends Model
{
  use HasFactory, SoftDeletes;

  public function category(): BelongsTo
  {
    return $this->belongsTo(Category::class);
  }

  public function purchaseItems(): HasMany
  {
    return $this->hasMany(PurchaseItem::class);
  }

  public function saleItems(): HasMany
  {
    return $this->hasMany(SaleItem::class);
  }

  public function stock(): HasOne
  {
    return $this->hasOne(Stock::class);
  }

  public function stockMovements(): HasMany
  {
    return $this->hasMany(StockMovement::class);
  }

  protected function casts(): array
  {
    return [
      'standard_sale_price' => 'decimal:2',
      'reorder_level' => 'integer',
      'is_active' => 'boolean',
    ];
  }
}
