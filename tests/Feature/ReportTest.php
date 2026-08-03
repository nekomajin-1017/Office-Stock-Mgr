<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_report_screen_from_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertSee(route('reports.index'), escape: false)
            ->assertSee('レポート');

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_sql_intentions_are_not_rendered_on_report_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertDontSee('SQLの意図');
    }

    public function test_unsold_products_are_extracted(): void
    {
        [$user, $product] = $this->createProductForReport('未販売');
        $this->actingAs($user)->get(route('reports.index'))->assertSee('未販売');
    }

    public function test_latest_purchase_price_is_displayed(): void
    {
        [$user, $product] = $this->createProductForReport('仕入商品');
        $supplier = Supplier::create(['code' => 'S', 'name' => 'S', 'is_active' => true]);
        foreach ([['2026-07-01', 100], ['2026-07-02', 150]] as [$purchaseDate, $unitPrice]) {
            $purchase = Purchase::create(['purchase_number' => uniqid('P'), 'supplier_id' => $supplier->id, 'purchase_date' => $purchaseDate, 'status' => 'confirmed', 'subtotal' => $unitPrice, 'tax_amount' => 0, 'total_amount' => $unitPrice, 'created_by' => $user->id]);
            PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => $unitPrice, 'subtotal' => $unitPrice, 'tax_amount' => 0]);
        }

        $this->actingAs($user)->get(route('reports.index'))->assertSee('150');
    }

    public function test_product_without_purchase_history_is_displayed(): void
    {
        [$user, $product] = $this->createProductForReport('履歴なし');
        $this->actingAs($user)->get(route('reports.index'))->assertSee('履歴なし');
    }

    public function test_shortage_products_are_extracted(): void
    {
        [$user, $product] = $this->createProductForReport('不足', 5);
        Stock::create(['product_id' => $product->id, 'quantity' => 3, 'average_cost' => 1]);
        $this->actingAs($user)->get(route('reports.index'))->assertSee('不足 2');
    }

    private function createProductForReport(string $productName, int $reorderLevel = 1): array
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => uniqid('C'), 'is_active' => true]);
        $supplier = Supplier::create(['code' => uniqid('S'), 'name' => '仕入先', 'is_active' => true]);
        $product = Product::create(['category_id' => $category->id, 'supplier_id' => $supplier->id, 'code' => uniqid('P'), 'name' => $productName, 'unit' => '個', 'standard_sale_price' => 1, 'reorder_level' => $reorderLevel, 'is_active' => true]);

        return [$user, $product];
    }
}
