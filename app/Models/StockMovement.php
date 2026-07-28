<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
  'product_id',
  'movement_type',
  'reference_type',
  'reference_id',
  'quantity_change',
  'unit_cost',
  'occurred_at',
  'created_by',
])]
class StockMovement extends Model
{
  use HasFactory;

  public const UPDATED_AT = null;

  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function reference(): MorphTo
  {
    return $this->morphTo();
  }

  public function referenceNumber(): string
  {
    return match (true) {
      $this->reference instanceof Purchase => $this->reference->purchase_number,
      $this->reference instanceof Sale => $this->reference->sale_number,
      default => '-',
    };
  }

  protected function casts(): array
  {
    return [
      'quantity_change' => 'integer',
      'unit_cost' => 'decimal:2',
      'occurred_at' => 'datetime',
    ];
  }
}
