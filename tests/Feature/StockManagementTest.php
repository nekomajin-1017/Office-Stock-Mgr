<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_list_displays_product_category_and_stock_information_with_eager_loading(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '文房具', 'is_active' => true]);
        $product = $this->createProduct($category, 'PEN-001', 10);
        Stock::create(['product_id' => $product->id, 'quantity' => 8, 'average_cost' => 125]);

        $this->actingAs($user)
            ->get(route('stocks.index'))
            ->assertOk()
            ->assertSee('PEN-001')
            ->assertSee($product->name)
            ->assertSee('文房具')
            ->assertSee('8 個')
            ->assertSee('125.00 円')
            ->assertSee('1,000.00 円')
            ->assertSee('要発注')
            ->assertViewHas('stocks', function (LengthAwarePaginator $stocks): bool {
                return $stocks->every(fn (Stock $stock): bool => $stock->relationLoaded('product')
                  && $stock->product->relationLoaded('category'));
            });
    }

    public function test_stock_list_calculates_total_inventory_value(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => '事務用品', 'is_active' => true]);
        $firstProduct = $this->createProduct($category, 'PEN-001', 1);
        $secondProduct = $this->createProduct($category, 'PAP-001', 1);
        Stock::create(['product_id' => $firstProduct->id, 'quantity' => 3, 'average_cost' => 120]);
        Stock::create(['product_id' => $secondProduct->id, 'quantity' => 2, 'average_cost' => 70]);

        $this->actingAs($user)
            ->get(route('stocks.index'))
            ->assertSee('在庫評価額合計: 500.00 円')
            ->assertViewHas('totalInventoryValue', 500.0);
    }

    public function test_stock_list_filters_by_keyword_category_and_shortage(): void
    {
        $user = User::factory()->create();
        $stationery = Category::create(['name' => '文房具', 'is_active' => true]);
        $paper = Category::create(['name' => '用紙', 'is_active' => true]);
        $shortProduct = $this->createProduct($stationery, 'PEN-001', 10);
        $sufficientProduct = $this->createProduct($stationery, 'PEN-002', 2);
        $otherCategoryProduct = $this->createProduct($paper, 'PAP-001', 10);
        Stock::create(['product_id' => $shortProduct->id, 'quantity' => 5, 'average_cost' => 100]);
        Stock::create(['product_id' => $sufficientProduct->id, 'quantity' => 5, 'average_cost' => 100]);
        Stock::create(['product_id' => $otherCategoryProduct->id, 'quantity' => 1, 'average_cost' => 100]);

        $this->actingAs($user)
            ->get(route('stocks.index', [
                'keyword' => 'PEN',
                'category_id' => $stationery->id,
                'shortage_only' => '1',
            ]))
            ->assertSee($shortProduct->name)
            ->assertDontSee($sufficientProduct->name)
            ->assertDontSee($otherCategoryProduct->name);
    }

    public function test_product_stock_movement_history_is_displayed_with_running_quantity_and_reference(): void
    {
        $user = User::factory()->create(['name' => '担当者']);
        $category = Category::create(['name' => '文房具', 'is_active' => true]);
        $product = $this->createProduct($category, 'PEN-001', 1);
        $purchase = $this->createPurchase($user);
        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'quantity_change' => 5,
            'unit_cost' => 100,
            'occurred_at' => '2026-07-28 09:00:00',
            'created_by' => $user->id,
        ]);
        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'quantity_change' => -2,
            'unit_cost' => null,
            'occurred_at' => '2026-07-28 10:00:00',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('stocks.movements', $product))
            ->assertOk()
            ->assertSee('入庫')
            ->assertSee('出庫')
            ->assertSee('+5')
            ->assertSee('-2')
            ->assertSee('5 個')
            ->assertSee('3 個')
            ->assertSee('PUR-001')
            ->assertSee('担当者');
    }

    private function createProduct(Category $category, string $code, int $reorderLevel): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $code.' 商品',
            'unit' => '個',
            'standard_sale_price' => 300,
            'reorder_level' => $reorderLevel,
            'is_active' => true,
        ]);
    }

    private function createPurchase(User $user): Purchase
    {
        $supplier = Supplier::create([
            'code' => 'SUP-001',
            'name' => '仕入先',
            'is_active' => true,
        ]);

        return Purchase::create([
            'purchase_number' => 'PUR-001',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-28',
            'status' => Purchase::STATUS_CONFIRMED,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'created_by' => $user->id,
        ]);
    }
}
