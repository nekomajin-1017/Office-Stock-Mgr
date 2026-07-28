<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleDraftManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_a_draft_sale(): void
    {
        [$user, $sale, $customer, $product] = $this->createDraftSale();

        $this->actingAs($user)
            ->put(route('sales.update', $sale), [
                'customer_id' => $customer->id,
                'sale_date' => '2026-07-28',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 200,
                ]],
            ])
            ->assertRedirect(route('sales.show', $sale));

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'subtotal' => 400,
            'total_amount' => 400,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 200,
        ]);
    }

    public function test_user_can_delete_a_draft_sale(): void
    {
        [$user, $sale] = $this->createDraftSale();

        $this->actingAs($user)
            ->delete(route('sales.destroy', $sale))
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
    }

    public function test_user_cannot_edit_or_delete_a_confirmed_sale(): void
    {
        [$user, $sale] = $this->createDraftSale(['status' => 'confirmed']);

        $this->actingAs($user)
            ->get(route('sales.edit', $sale))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('sales.destroy', $sale))
            ->assertForbidden();
    }

    private function createDraftSale(array $saleAttributes = []): array
    {
        $user = User::factory()->create();
        $customer = Customer::create(['code' => 'C001', 'name' => '顧客', 'is_active' => true]);
        $supplier = Supplier::create(['code' => 'S001', 'name' => '仕入先', 'is_active' => true]);
        $category = Category::create(['name' => 'カテゴリ', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'code' => 'P001',
            'name' => '商品',
            'unit' => '個',
            'standard_sale_price' => 200,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        Stock::create(['product_id' => $product->id, 'quantity' => 10, 'average_cost' => 100]);
        $sale = Sale::create($saleAttributes + [
            'sale_number' => 'SAL-001',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-27',
            'status' => 'draft',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'created_by' => $user->id,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'cost_unit_price' => 0,
            'subtotal' => 100,
            'cost_amount' => 0,
            'tax_amount' => 0,
        ]);

        return [$user, $sale, $customer, $product];
    }
}
