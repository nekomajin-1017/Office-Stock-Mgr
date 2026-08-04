<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleInventoryService
{
    public function confirm(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $sale = Sale::query()->lockForUpdate()->with('items')->findOrFail($sale->id);

            if (! $sale->isDraft()) {
                throw ValidationException::withMessages([
                    'sale' => '確定済みの販売伝票は再度確定できません。',
                ]);
            }

            $stocks = Stock::query()
                ->whereIn('product_id', $sale->items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($sale->items as $item) {
                $stock = $stocks->get($item->product_id);

                if (! $stock || $stock->quantity < $item->quantity) {
                    throw ValidationException::withMessages(['sale' => '在庫数が不足しています。']);
                }

                $stock->decrement('quantity', $item->quantity);
                $this->recordMovement($sale, $item, 'sale', -$item->quantity);
            }

            $sale->update([
                'status' => Sale::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);
        }, attempts: 3);
    }

    public function cancel(Sale $sale, string $reason): void
    {
        DB::transaction(function () use ($sale, $reason): void {
            $sale = $this->lockedConfirmedSale($sale);
            $this->reverseSale($sale, 'sale_cancel');
            $sale->update([
                'status' => Sale::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);
        }, attempts: 3);
    }

    public function correct(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $sale = $this->lockedConfirmedSale($sale);
            $this->reverseSale($sale, 'sale_correction');
            $sale->update([
                'status' => Sale::STATUS_DRAFT,
                'confirmed_at' => null,
                'confirmed_by' => null,
            ]);
        }, attempts: 3);
    }

    private function lockedConfirmedSale(Sale $sale): Sale
    {
        $lockedSale = Sale::query()->lockForUpdate()->with('items')->findOrFail($sale->id);

        if (! $lockedSale->isConfirmed()) {
            throw ValidationException::withMessages([
                'sale' => '確定済み伝票のみ処理できます。',
            ]);
        }

        return $lockedSale;
    }

    private function reverseSale(Sale $sale, string $movementType): void
    {
        $items = $sale->items->groupBy('product_id')->sortKeys();
        $stocks = Stock::query()
            ->whereIn('product_id', $items->keys())
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        foreach ($items as $productId => $saleItems) {
            $stock = $stocks->get($productId);

            if (! $stock) {
                throw ValidationException::withMessages([
                    'sale' => '対象商品の在庫レコードが見つかりません。',
                ]);
            }

            $stock->increment('quantity', $saleItems->sum('quantity'));

            foreach ($saleItems as $item) {
                $this->recordMovement($sale, $item, $movementType, $item->quantity);
            }
        }
    }

    private function recordMovement(Sale $sale, object $item, string $type, int $quantity): void
    {
        StockMovement::create([
            'product_id' => $item->product_id,
            'movement_type' => $type,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'quantity_change' => $quantity,
            'unit_cost' => $item->cost_unit_price,
            'occurred_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }
}
