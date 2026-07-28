<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    private const SALE_QUANTITY_DIVISOR = 3;

    private const MINIMUM_MARGIN = 1;

    public function run(): void
    {
        $administrator = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->firstOrFail();
        $customers = Customer::query()->orderBy('code')->get();
        $products = Product::query()
            ->with('stock')
            ->orderBy('code')
            ->get();

        $customers->each(function (Customer $customer, int $index) use ($administrator, $customers, $products): void {
            $saleProducts = $products->filter(function (Product $product) use ($customers, $index): bool {
                return $product->id % $customers->count() === $index;
            });

            $this->seedSale($customer, $index, $administrator, $saleProducts);
        });
    }

    private function seedSale(Customer $customer, int $index, User $administrator, Collection $products): void
    {
        DB::transaction(function () use ($customer, $index, $administrator, $products): void {
            $sale = Sale::updateOrCreate(
                ['sale_number' => sprintf('SAL-SEED-%03d', $index + 1)],
                [
                    'customer_id' => $customer->id,
                    'sale_date' => now()->subDays(10 - $index)->toDateString(),
                    'status' => 'confirmed',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                    'created_by' => $administrator->id,
                    'confirmed_at' => now()->subDays(10 - $index),
                    'confirmed_by' => $administrator->id,
                ],
            );

            $sale->items()->delete();
            StockMovement::query()
                ->where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->delete();

            $subtotal = 0;

            foreach ($products as $product) {
                $stock = $product->stock;

                if (! $stock || $stock->quantity < self::SALE_QUANTITY_DIVISOR) {
                    continue;
                }

                $quantity = intdiv($stock->quantity, self::SALE_QUANTITY_DIVISOR);
                $unitPrice = max(
                    (int) $product->standard_sale_price,
                    (int) ceil((float) $stock->average_cost) + self::MINIMUM_MARGIN,
                );
                $costUnitPrice = (int) $stock->average_cost;
                $lineSubtotal = $quantity * $unitPrice;
                $costAmount = $quantity * $costUnitPrice;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_unit_price' => $costUnitPrice,
                    'subtotal' => $lineSubtotal,
                    'cost_amount' => $costAmount,
                    'tax_amount' => 0,
                ]);
                $stock->decrement('quantity', $quantity);
                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'sale',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'quantity_change' => -$quantity,
                    'unit_cost' => $costUnitPrice,
                    'occurred_at' => $sale->confirmed_at,
                    'created_by' => $administrator->id,
                ]);

                $subtotal += $lineSubtotal;
            }

            $sale->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
            ]);
        });
    }
}
