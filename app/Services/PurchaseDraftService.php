<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseDraftService
{
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data): Purchase {
            $items = collect($data['items'])->map(fn (array $item): array => $this->prepareItem($item));
            $subtotal = $items->sum('subtotal');
            $purchase = Purchase::create([
                'purchase_number' => $this->generateNumber(),
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'status' => Purchase::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'created_by' => auth()->id(),
            ]);

            $purchase->items()->createMany($items->all());

            return $purchase;
        });
    }

    public function update(Purchase $purchase, array $data): void
    {
        DB::transaction(function () use ($purchase, $data): void {
            $items = collect($data['items'])->map(fn (array $item): array => $this->prepareItem($item));
            $subtotal = $items->sum('subtotal');

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
            ]);
            $purchase->items()->delete();
            $purchase->items()->createMany($items->all());
        });
    }

    private function prepareItem(array $item): array
    {
        return [
            ...$item,
            'subtotal' => $item['quantity'] * $item['unit_price'],
            'tax_amount' => 0,
        ];
    }

    private function generateNumber(): string
    {
        do {
            $number = 'PUR-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Purchase::query()->where('purchase_number', $number)->exists());

        return $number;
    }
}
