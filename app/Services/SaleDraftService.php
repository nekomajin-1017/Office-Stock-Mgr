<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleDraftService
{
    public function create(array $data): Sale
    {
        $this->ensureSufficientStock($data['items']);

        return DB::transaction(function () use ($data): Sale {
            $items = $this->prepareItems($data['items']);
            $subtotal = $items->sum('subtotal');
            $sale = Sale::create([
                'sale_number' => 'SAL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'customer_id' => $data['customer_id'],
                'sale_date' => $data['sale_date'],
                'status' => Sale::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'created_by' => auth()->id(),
            ]);

            $sale->items()->createMany($items->all());

            return $sale;
        });
    }

    public function update(Sale $sale, array $data): void
    {
        $this->ensureSufficientStock($data['items']);

        DB::transaction(function () use ($sale, $data): void {
            $items = $this->prepareItems($data['items']);
            $subtotal = $items->sum('subtotal');

            $sale->update([
                'customer_id' => $data['customer_id'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
            ]);
            $sale->items()->delete();
            $sale->items()->createMany($items->all());
        });
    }

    private function ensureSufficientStock(array $items): void
    {
        $products = Product::query()
            ->with('stock')
            ->whereIn('id', collect($items)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        foreach ($items as $index => $item) {
            $stock = $products[$item['product_id']]->stock;

            if (! $stock || $stock->quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => '在庫数が不足しています。',
                ]);
            }
        }
    }

    private function prepareItems(array $items)
    {
        return collect($items)->map(fn (array $item): array => [
            ...$item,
            'cost_unit_price' => 0,
            'subtotal' => $item['quantity'] * $item['unit_price'],
            'cost_amount' => 0,
            'tax_amount' => 0,
        ]);
    }
}
