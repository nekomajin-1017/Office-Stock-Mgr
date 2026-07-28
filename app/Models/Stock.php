<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'quantity', 'average_cost'])]
class Stock extends Model
{
    use HasFactory;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryValue(): float
    {
        return $this->quantity * (float) $this->average_cost;
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'average_cost' => 'decimal:2',
        ];
    }
}
