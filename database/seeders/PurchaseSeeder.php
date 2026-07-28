<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseSeeder extends Seeder
{
    private const MINIMUM_QUANTITY = 50;

    private const MAXIMUM_QUANTITY = 1000;

    private const WHOLESALE_PRICE_RATE = 0.65;

    public function run(): void
    {
        $administrator = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->firstOrFail();

        Supplier::query()
            ->orderBy('code')
            ->each(function (Supplier $supplier, int $index) use ($administrator): void {
                DB::transaction(function () use ($supplier, $index, $administrator): void {
                    $purchase = Purchase::updateOrCreate(
                        ['purchase_number' => sprintf('PUR-SEED-%03d', $index + 1)],
                        [
                            'supplier_id' => $supplier->id,
                            'purchase_date' => now()->subDays(30 - $index)->toDateString(),
                            'status' => Purchase::STATUS_CONFIRMED,
                            'subtotal' => 0,
                            'tax_amount' => 0,
                            'total_amount' => 0,
                            'created_by' => $administrator->id,
                            'confirmed_at' => now()->subDays(30 - $index),
                            'confirmed_by' => $administrator->id,
                        ],
                    );

                    $purchase->items()->delete();
                    StockMovement::query()
                        ->where('reference_type', Purchase::class)
                        ->where('reference_id', $purchase->id)
                        ->delete();

                    $subtotal = 0;

                    $products = Product::query()
                        ->where('supplier_id', $supplier->id)
                        ->orderBy('code')
                        ->get();

                    foreach ($products as $product) {
                        $quantity = $this->purchaseQuantity($product);
                        $unitPrice = $this->purchaseUnitPrice($product);
                        $lineSubtotal = $quantity * $unitPrice;

                        $purchase->items()->create([
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'subtotal' => $lineSubtotal,
                            'tax_amount' => 0,
                        ]);

                        Stock::query()
                            ->where('product_id', $product->id)
                            ->update([
                                'quantity' => $quantity,
                                'average_cost' => $unitPrice,
                            ]);

                        StockMovement::create([
                            'product_id' => $product->id,
                            'movement_type' => 'purchase',
                            'reference_type' => Purchase::class,
                            'reference_id' => $purchase->id,
                            'quantity_change' => $quantity,
                            'unit_cost' => $unitPrice,
                            'occurred_at' => $purchase->confirmed_at,
                            'created_by' => $administrator->id,
                        ]);

                        $subtotal += $lineSubtotal;
                    }

                    $purchase->update([
                        'subtotal' => $subtotal,
                        'total_amount' => $subtotal,
                    ]);
                });
            });
    }

    private function purchaseQuantity(Product $product): int
    {
        $quantityRange = self::MAXIMUM_QUANTITY - self::MINIMUM_QUANTITY + 1;

        return self::MINIMUM_QUANTITY + (($product->id * 71) % $quantityRange);
    }

    private function purchaseUnitPrice(Product $product): int
    {
        return (int) round((float) $product->standard_sale_price * self::WHOLESALE_PRICE_RATE);
    }
}
