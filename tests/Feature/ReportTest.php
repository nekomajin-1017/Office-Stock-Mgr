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

    public function test_unsold_products_are_extracted(): void
    {
        [$user, $product] = $this->createProductForReport('未販売');
        $this->actingAs($user)->get(route('reports.index'))->assertSee('未販売');
    }

    public function test_purchase_summary_respects_period_filter(): void
    {
        [$user, $product] = $this->createProductForReport('集計対象');
        $supplier = Supplier::create(['code' => 'SUMMARY', 'name' => '集計仕入先', 'is_active' => true]);
        $this->createConfirmedPurchase($user, $supplier, $product, '2026-07-01', 2, 100);
        $this->createConfirmedPurchase($user, $supplier, $product, '2026-08-01', 3, 200);

        $this->actingAs($user)
            ->get(route('reports.index', ['from' => '2026-07-01', 'to' => '2026-07-31']))
            ->assertViewHas('purchaseSummary', function (object $summary): bool {
                return (int) $summary->total_quantity === 2
                  && (float) $summary->total_amount === 200.0;
            })
            ->assertSee('総仕入数量')
            ->assertSee('仕入総額');
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

    private function createConfirmedPurchase(
        User $user,
        Supplier $supplier,
        Product $product,
        string $purchaseDate,
        int $quantity,
        int $unitPrice,
    ): void {
        $subtotal = $quantity * $unitPrice;
        $purchase = Purchase::create([
            'purchase_number' => uniqid('P'),
            'supplier_id' => $supplier->id,
            'purchase_date' => $purchaseDate,
            'status' => 'confirmed',
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'total_amount' => $subtotal,
            'created_by' => $user->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax_amount' => 0,
        ]);
    }
}
