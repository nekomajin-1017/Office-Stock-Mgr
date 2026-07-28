<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_sales_quantity_is_aggregated(): void
    {
        [$user, $highSellingProduct] = $this->createReportData();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertViewHas('salesRanking', function (Collection $products) use ($highSellingProduct): bool {
                return $products->firstWhere('id', $highSellingProduct->id)?->sales_quantity === 5;
            });
    }

    public function test_average_sales_quantity_is_calculated(): void
    {
        [$user] = $this->createReportData();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertViewHas('averageSalesQuantity', 3.0);
    }

    public function test_products_above_average_sales_quantity_are_extracted(): void
    {
        [$user, $highSellingProduct, $lowSellingProduct] = $this->createReportData();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertViewHas('aboveAverageProducts', function (Collection $products) use ($highSellingProduct, $lowSellingProduct): bool {
                return $products->contains('id', $highSellingProduct->id)
                  && ! $products->contains('id', $lowSellingProduct->id);
            });
    }

    public function test_sales_ranking_is_sorted_by_quantity(): void
    {
        [$user, $highSellingProduct, $lowSellingProduct] = $this->createReportData();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertViewHas('salesRanking', function (Collection $products) use ($highSellingProduct, $lowSellingProduct): bool {
                return $products->pluck('id')->all() === [$highSellingProduct->id, $lowSellingProduct->id];
            });
    }

    public function test_period_filter_is_applied(): void
    {
        [$user, $highSellingProduct] = $this->createReportData();

        $this->actingAs($user)
            ->get(route('reports.index', ['from' => '2026-08-01']))
            ->assertViewHas('salesRanking', function (Collection $products) use ($highSellingProduct): bool {
                return ! $products->contains('id', $highSellingProduct->id);
            });
    }

    private function createReportData(): array
    {
        $user = User::factory()->create();
        $customer = Customer::create(['code' => 'C001', 'name' => '顧客', 'is_active' => true]);
        $supplier = Supplier::create(['code' => 'S001', 'name' => '仕入先', 'is_active' => true]);
        $category = Category::create(['name' => 'カテゴリ', 'is_active' => true]);
        $highSellingProduct = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'code' => 'P001',
            'name' => '商品A',
            'unit' => '個',
            'standard_sale_price' => 1,
            'reorder_level' => 0,
            'is_active' => true,
        ]);
        $lowSellingProduct = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'code' => 'P002',
            'name' => '商品B',
            'unit' => '個',
            'standard_sale_price' => 1,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $this->createConfirmedSale($user, $customer, $highSellingProduct, 5);
        $this->createConfirmedSale($user, $customer, $lowSellingProduct, 1);

        return [$user, $highSellingProduct, $lowSellingProduct];
    }

    private function createConfirmedSale(User $user, Customer $customer, Product $product, int $quantity): void
    {
        $sale = Sale::create([
            'sale_number' => uniqid('S'),
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-28',
            'status' => 'confirmed',
            'subtotal' => $quantity,
            'tax_amount' => 0,
            'total_amount' => $quantity,
            'created_by' => $user->id,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => 1,
            'cost_unit_price' => 0,
            'subtotal' => $quantity,
            'cost_amount' => 0,
            'tax_amount' => 0,
        ]);
    }
}
