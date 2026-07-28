<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_confirmation_updates_status_stock_and_movement(): void
    {
        [$u,$s,$p] = $this->sale(5, 3);
        $this->actingAs($u)->post(route('sales.confirm', $s))->assertRedirect();
        $this->assertDatabaseHas('sales', ['id' => $s->id, 'status' => 'confirmed', 'confirmed_by' => $u->id]);
        $this->assertDatabaseHas('stocks', ['product_id' => $p->id, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'movement_type' => 'sale', 'quantity_change' => -3, 'reference_id' => $s->id]);
    }

    public function test_sale_confirmation_succeeds(): void
    {
        [$u,$s] = $this->sale(5, 1);
        $this->actingAs($u)->post(route('sales.confirm', $s))->assertRedirect();
        $this->assertDatabaseHas('sales', ['id' => $s->id, 'status' => 'confirmed']);
    }

    public function test_sale_confirmation_decreases_stock(): void
    {
        [$u,$s,$p] = $this->sale(5, 3);
        $this->actingAs($u)->post(route('sales.confirm', $s));
        $this->assertDatabaseHas('stocks', ['product_id' => $p->id, 'quantity' => 2]);
    }

    public function test_sale_confirmation_creates_stock_movement(): void
    {
        [$u,$s,$p] = $this->sale(5, 3);
        $this->actingAs($u)->post(route('sales.confirm', $s));
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'movement_type' => 'sale', 'reference_id' => $s->id]);
    }

    public function test_sale_can_reduce_stock_to_zero(): void
    {
        [$u,$s,$p] = $this->sale(3, 3);
        $this->actingAs($u)->post(route('sales.confirm', $s));
        $this->assertDatabaseHas('stocks', ['product_id' => $p->id, 'quantity' => 0]);
    }

    public function test_sale_rejects_insufficient_stock(): void
    {
        [$u,$s,$p] = $this->sale(2, 3);
        $this->actingAs($u)->post(route('sales.confirm', $s))->assertSessionHasErrors('sale');
        $this->assertDatabaseHas('stocks', ['product_id' => $p->id, 'quantity' => 2]);
    }

    public function test_sale_cannot_be_confirmed_twice(): void
    {
        [$u,$s,$p] = $this->sale(5, 3);
        $this->actingAs($u)->post(route('sales.confirm', $s));
        $this->actingAs($u)->post(route('sales.confirm', $s))->assertSessionHasErrors('sale');
        $this->assertDatabaseHas('stocks', ['product_id' => $p->id, 'quantity' => 2]);
    }

    public function test_confirmation_rolls_back_when_later_stock_is_missing(): void
    {
        [$u,$s,$p] = $this->sale(5, 3);
        $category = $p->category;
        $missing = Product::create(['category_id' => $category->id, 'code' => 'P2', 'name' => 'P2', 'unit' => '個', 'standard_sale_price' => 1, 'reorder_level' => 1, 'is_active' => true]);
        SaleItem::create(['sale_id' => $s->id, 'product_id' => $missing->id, 'quantity' => 1, 'unit_price' => 1, 'cost_unit_price' => 0, 'subtotal' => 1, 'cost_amount' => 0, 'tax_amount' => 0]);
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($u)->post(route('sales.confirm', $s));
            $this->fail('例外が必要です。');
        } catch (ValidationException) {
            $this->assertDatabaseHas('stocks', ['product_id' => $p->id, 'quantity' => 5]);
            $this->assertDatabaseHas('sales', ['id' => $s->id, 'status' => 'draft']);
        }
    }

    private function sale(int $stockQuantity, int $saleQuantity): array
    {
        $u = User::factory()->create();
        $c = Customer::create(['code' => 'C1', 'name' => 'C', 'is_active' => true]);
        $cat = Category::create(['name' => 'カテゴリ', 'is_active' => true]);
        $p = Product::create(['category_id' => $cat->id, 'code' => 'P1', 'name' => 'P', 'unit' => '個', 'standard_sale_price' => 1, 'reorder_level' => 1, 'is_active' => true]);
        Stock::create(['product_id' => $p->id, 'quantity' => $stockQuantity, 'average_cost' => 10]);
        $s = Sale::create(['sale_number' => 'S1', 'customer_id' => $c->id, 'sale_date' => '2026-07-28', 'status' => 'draft', 'subtotal' => 1, 'tax_amount' => 0, 'total_amount' => 1, 'created_by' => $u->id]);
        SaleItem::create(['sale_id' => $s->id, 'product_id' => $p->id, 'quantity' => $saleQuantity, 'unit_price' => 1, 'cost_unit_price' => 10, 'subtotal' => 1, 'cost_amount' => 1, 'tax_amount' => 0]);

        return [$u, $s, $p];
    }
}
