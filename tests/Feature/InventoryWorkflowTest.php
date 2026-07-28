<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_then_sale_workflow(): void
    {
        [$a,$p,$s,$stock] = $this->records();
        $this->actingAs($a)->post(route('purchases.confirm', $p));
        $this->actingAs($a)->post(route('sales.confirm', $s));
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 2]);
    }

    public function test_purchase_cancellation_reverses_stock(): void
    {
        [$a,$p,,$stock] = $this->records();
        $this->actingAs($a)->post(route('purchases.confirm', $p));
        $this->actingAs($a)->post(route('purchases.cancel', $p), ['reason' => '誤登録']);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 0]);
    }

    public function test_sale_cancellation_restores_stock(): void
    {
        [$a,$p,$s,$stock] = $this->records();
        $this->actingAs($a)->post(route('purchases.confirm', $p));
        $this->actingAs($a)->post(route('sales.confirm', $s));
        $this->actingAs($a)->post(route('sales.cancel', $s), ['reason' => '返品']);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 5]);
    }

    public function test_non_admin_cannot_cancel(): void
    {
        [$a,$p] = $this->records();
        $this->actingAs($a)->post(route('purchases.confirm', $p));
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->post(route('purchases.cancel', $p), ['reason' => 'x'])->assertForbidden();
    }

    private function records(): array
    {
        $a = User::factory()->create(['role' => 'admin']);
        $sup = Supplier::create(['code' => 'S', 'name' => 'S', 'is_active' => true]);
        $cus = Customer::create(['code' => 'C', 'name' => 'C', 'is_active' => true]);
        $cat = Category::create(['name' => 'X', 'is_active' => true]);
        $pro = Product::create(['category_id' => $cat->id, 'code' => 'P', 'name' => 'P', 'unit' => '個', 'standard_sale_price' => 1, 'reorder_level' => 1, 'is_active' => true]);
        $st = Stock::create(['product_id' => $pro->id, 'quantity' => 0, 'average_cost' => 0]);
        $p = Purchase::create(['purchase_number' => 'P1', 'supplier_id' => $sup->id, 'purchase_date' => '2026-07-28', 'status' => 'draft', 'subtotal' => 5, 'tax_amount' => 0, 'total_amount' => 5, 'created_by' => $a->id]);
        PurchaseItem::create(['purchase_id' => $p->id, 'product_id' => $pro->id, 'quantity' => 5, 'unit_price' => 1, 'subtotal' => 5, 'tax_amount' => 0]);
        $s = Sale::create(['sale_number' => 'S1', 'customer_id' => $cus->id, 'sale_date' => '2026-07-28', 'status' => 'draft', 'subtotal' => 3, 'tax_amount' => 0, 'total_amount' => 3, 'created_by' => $a->id]);
        SaleItem::create(['sale_id' => $s->id, 'product_id' => $pro->id, 'quantity' => 3, 'unit_price' => 1, 'cost_unit_price' => 1, 'subtotal' => 3, 'cost_amount' => 3, 'tax_amount' => 0]);

        return [$a, $p, $s, $st];
    }
}
