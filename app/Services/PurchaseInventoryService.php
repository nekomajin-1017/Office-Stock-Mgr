<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PurchaseInventoryService
{
    private const CURRENCY_FACTOR = 100;

    public function confirm(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $purchase = Purchase::query()->lockForUpdate()->with('items')->findOrFail($purchase->id);

            if (! $purchase->isDraft()) {
                throw ValidationException::withMessages([
                    'purchase' => '確定済みの仕入伝票は再度確定できません。',
                ]);
            }

            $items = $purchase->items->groupBy('product_id')->sortKeys();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase' => '明細がない仕入伝票は確定できません。',
                ]);
            }

            $stocks = $this->lockedStocks($items);

            foreach ($items as $productId => $productItems) {
                $stock = $stocks->get($productId);

                if (! $stock) {
                    throw new RuntimeException('対象商品の在庫レコードが見つかりません。');
                }

                $this->applyPurchase($stock, $productItems, $purchase);
            }

            $purchase->update([
                'status' => Purchase::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);
        }, attempts: 3);
    }

    public function cancel(Purchase $purchase, string $reason): void
    {
        DB::transaction(function () use ($purchase, $reason): void {
            $purchase = $this->lockedConfirmedPurchase($purchase);
            $this->reversePurchase($purchase, 'purchase_cancel');
            $purchase->update([
                'status' => Purchase::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);
        }, attempts: 3);
    }

    public function correct(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $purchase = $this->lockedConfirmedPurchase($purchase);
            $this->reversePurchase($purchase, 'purchase_correction');
            $purchase->update([
                'status' => Purchase::STATUS_DRAFT,
                'confirmed_at' => null,
                'confirmed_by' => null,
            ]);
        }, attempts: 3);
    }

    private function lockedConfirmedPurchase(Purchase $purchase): Purchase
    {
        $lockedPurchase = Purchase::query()->lockForUpdate()->with('items')->findOrFail($purchase->id);

        if (! $lockedPurchase->isConfirmed()) {
            throw ValidationException::withMessages([
                'purchase' => '確定済み伝票のみ処理できます。',
            ]);
        }

        return $lockedPurchase;
    }

    private function reversePurchase(Purchase $purchase, string $movementType): void
    {
        $items = $purchase->items->groupBy('product_id')->sortKeys();
        $stocks = $this->lockedStocks($items);

        foreach ($items as $productId => $productItems) {
            $stock = $stocks->get($productId);
            $quantity = $productItems->sum('quantity');

            if (! $stock || $stock->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'purchase' => '訂正・取消に必要な在庫が不足しています。',
                ]);
            }

            $remainingQuantity = $stock->quantity - $quantity;
            $purchaseCost = $productItems->sum(
                fn ($item): int => $item->quantity * $this->toCents($item->unit_price),
            );
            $remainingCost = max(
                0,
                ($stock->quantity * $this->toCents($stock->average_cost)) - $purchaseCost,
            );
            $remainingAverageCost = $remainingQuantity === 0
                ? 0
                : $this->roundedUnitAmount($remainingCost, $remainingQuantity);

            $stock->update([
                'quantity' => $remainingQuantity,
                'average_cost' => number_format($remainingAverageCost / self::CURRENCY_FACTOR, 2, '.', ''),
            ]);
            $this->recordMovements($productItems, $purchase, $movementType, -1);
        }
    }

    private function applyPurchase(Stock $stock, Collection $items, Purchase $purchase): void
    {
        $purchaseQuantity = $items->sum('quantity');
        $purchaseCost = $items->sum(
            fn ($item): int => $item->quantity * $this->toCents($item->unit_price),
        );
        $currentCost = $stock->quantity * $this->toCents($stock->average_cost);
        $newQuantity = $stock->quantity + $purchaseQuantity;
        $newAverageCost = $this->roundedUnitAmount($currentCost + $purchaseCost, $newQuantity);

        $stock->update([
            'quantity' => $newQuantity,
            'average_cost' => number_format($newAverageCost / self::CURRENCY_FACTOR, 2, '.', ''),
        ]);
        $this->recordMovements($items, $purchase, 'purchase', 1);
    }

    private function lockedStocks(Collection $items): Collection
    {
        return Stock::query()
            ->whereIn('product_id', $items->keys())
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');
    }

    private function recordMovements(Collection $items, Purchase $purchase, string $type, int $direction): void
    {
        foreach ($items as $item) {
            StockMovement::create([
                'product_id' => $item->product_id,
                'movement_type' => $type,
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'quantity_change' => $direction * $item->quantity,
                'unit_cost' => $item->unit_price,
                'occurred_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function toCents(string|float|int $amount): int
    {
        return (int) round((float) $amount * self::CURRENCY_FACTOR, 0, PHP_ROUND_HALF_UP);
    }

    private function roundedUnitAmount(int $totalAmount, int $quantity): int
    {
        return intdiv($totalAmount + intdiv($quantity, 2), $quantity);
    }
}
