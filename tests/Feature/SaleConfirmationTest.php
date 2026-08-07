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
        [$user, $sale, $product] = $this->createSaleWithStock(5, 3);
        $this->actingAs($user)->post(route('sales.confirm', $sale))->assertRedirect();
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'confirmed', 'confirmed_by' => $user->id]);
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'movement_type' => 'sale', 'quantity_change' => -3, 'reference_id' => $sale->id]);
    }

    public function test_sale_can_reduce_stock_to_zero(): void
    {
        [$user, $sale, $product] = $this->createSaleWithStock(3, 3);
        $this->actingAs($user)->post(route('sales.confirm', $sale));
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 0]);
    }

    public function test_sale_rejects_insufficient_stock(): void
    {
        [$user, $sale, $product] = $this->createSaleWithStock(2, 3);
        $this->actingAs($user)->post(route('sales.confirm', $sale))->assertSessionHasErrors('sale');
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_sale_cannot_be_confirmed_twice(): void
    {
        [$user, $sale, $product] = $this->createSaleWithStock(5, 3);
        $this->actingAs($user)->post(route('sales.confirm', $sale));
        $this->actingAs($user)->post(route('sales.confirm', $sale))->assertSessionHasErrors('sale');
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_confirmation_rolls_back_when_later_stock_is_missing(): void
    {
        [$user, $sale, $product] = $this->createSaleWithStock(5, 3);
        $category = $product->category;
        $missing = Product::factory()->create(['category_id' => $category->id, 'code' => 'P2', 'name' => 'P2']);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $missing->id, 'quantity' => 1, 'unit_price' => 1, 'cost_unit_price' => 0, 'subtotal' => 1, 'cost_amount' => 0, 'tax_amount' => 0]);
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($user)->post(route('sales.confirm', $sale));
            $this->fail('例外が必要です。');
        } catch (ValidationException) {
            $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 5]);
            $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'draft']);
        }
    }

    private function createSaleWithStock(int $stockQuantity, int $saleQuantity): array
    {
        $user = User::factory()->create();
        $customer = Customer::create(['code' => 'C1', 'name' => 'C', 'is_active' => true]);
        $category = Category::create(['name' => 'カテゴリ', 'is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'code' => 'P1',
            'name' => 'P',
            'standard_sale_price' => 1,
            'reorder_level' => 1,
        ]);
        Stock::create(['product_id' => $product->id, 'quantity' => $stockQuantity, 'average_cost' => 10]);
        $sale = Sale::create(['sale_number' => 'S1', 'customer_id' => $customer->id, 'sale_date' => '2026-07-28', 'status' => 'draft', 'subtotal' => 1, 'tax_amount' => 0, 'total_amount' => 1, 'created_by' => $user->id]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => $saleQuantity, 'unit_price' => 1, 'cost_unit_price' => 10, 'subtotal' => 1, 'cost_amount' => 1, 'tax_amount' => 0]);

        return [$user, $sale, $product];
    }
}
