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

    public function test_unsold_products_are_extracted(): void
    {
        [$u,$p] = $this->product('未販売');
        $this->actingAs($u)->get(route('reports.index'))->assertSee('未販売');
    }

    public function test_latest_purchase_price_is_displayed(): void
    {
        [$u,$p] = $this->product('仕入商品');
        $sup = Supplier::create(['code' => 'S', 'name' => 'S', 'is_active' => true]);
        foreach ([['2026-07-01', 100], ['2026-07-02', 150]] as [$date,$price]) {
            $purchase = Purchase::create(['purchase_number' => uniqid('P'), 'supplier_id' => $sup->id, 'purchase_date' => $date, 'status' => 'confirmed', 'subtotal' => $price, 'tax_amount' => 0, 'total_amount' => $price, 'created_by' => $u->id]);
            PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $p->id, 'quantity' => 1, 'unit_price' => $price, 'subtotal' => $price, 'tax_amount' => 0]);
        }$this->actingAs($u)->get(route('reports.index'))->assertSee('150');
    }

    public function test_product_without_purchase_history_is_displayed(): void
    {
        [$u,$p] = $this->product('履歴なし');
        $this->actingAs($u)->get(route('reports.index'))->assertSee('履歴なし');
    }

    public function test_shortage_products_are_extracted(): void
    {
        [$u,$p] = $this->product('不足', 5);
        Stock::create(['product_id' => $p->id, 'quantity' => 3, 'average_cost' => 1]);
        $this->actingAs($u)->get(route('reports.index'))->assertSee('不足 2');
    }

    private function product(string $name, int $reorder = 1): array
    {
        $u = User::factory()->create();
        $c = Category::create(['name' => uniqid('C'), 'is_active' => true]);
        $s = Supplier::create(['code' => uniqid('S'), 'name' => '仕入先', 'is_active' => true]);
        $p = Product::create(['category_id' => $c->id, 'supplier_id' => $s->id, 'code' => uniqid('P'), 'name' => $name, 'unit' => '個', 'standard_sale_price' => 1, 'reorder_level' => $reorder, 'is_active' => true]);

        return [$u, $p];
    }
}
