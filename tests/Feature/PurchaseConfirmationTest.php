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
use RuntimeException;
use Tests\TestCase;

class PurchaseConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_purchase_can_be_confirmed_and_confirmation_details_are_saved(): void
    {
        [$user, $purchase] = $this->createPurchase();

        $this->actingAs($user)
            ->post(route('purchases.confirm', $purchase))
            ->assertRedirect(route('purchases.show', $purchase));

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => Purchase::STATUS_CONFIRMED,
            'confirmed_by' => $user->id,
        ]);
        $this->assertNotNull($purchase->fresh()->confirmed_at);
    }

    public function test_confirming_purchase_increases_stock_and_recalculates_average_cost(): void
    {
        [$user, $purchase, $product] = $this->createPurchase([
            'stock_quantity' => 10,
            'stock_average_cost' => 100,
            'item_quantity' => 5,
            'item_unit_price' => 160,
        ]);

        $this->actingAs($user)->post(route('purchases.confirm', $purchase));

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 15,
            'average_cost' => 120,
        ]);
    }

    public function test_first_purchase_with_zero_stock_sets_purchase_price_as_average_cost(): void
    {
        [$user, $purchase, $product] = $this->createPurchase([
            'stock_quantity' => 0,
            'stock_average_cost' => 0,
            'item_quantity' => 3,
            'item_unit_price' => '123.45',
        ]);

        $this->actingAs($user)->post(route('purchases.confirm', $purchase));

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 3,
            'average_cost' => '123.45',
        ]);
    }

    public function test_average_cost_is_rounded_half_up_to_two_decimal_places(): void
    {
        [$user, $purchase, $product] = $this->createPurchase([
            'stock_quantity' => 1,
            'stock_average_cost' => 100,
            'item_quantity' => 2,
            'item_unit_price' => '100.01',
        ]);

        $this->actingAs($user)->post(route('purchases.confirm', $purchase));

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'quantity' => 3,
            'average_cost' => '100.01',
        ]);
    }

    public function test_confirming_purchase_creates_stock_movement(): void
    {
        [$user, $purchase, $product] = $this->createPurchase();

        $this->actingAs($user)->post(route('purchases.confirm', $purchase));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'quantity_change' => 5,
            'unit_cost' => 160,
            'created_by' => $user->id,
        ]);
    }

    public function test_confirmed_purchase_cannot_be_confirmed_twice(): void
    {
        [$user, $purchase, $product] = $this->createPurchase();

        $this->actingAs($user)->post(route('purchases.confirm', $purchase));
        $this->actingAs($user)
            ->post(route('purchases.confirm', $purchase))
            ->assertSessionHasErrors('purchase');
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 5]);
    }

    public function test_transaction_is_rolled_back_when_a_stock_record_is_missing(): void
    {
        [$user, $purchase, $product] = $this->createPurchase();
        $category = Category::create(['name' => '用紙', 'is_active' => true]);
        $missingStockProduct = $this->createProduct($category, 'PAPER-001');
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $missingStockProduct->id,
            'quantity' => 2,
            'unit_price' => 200,
            'subtotal' => 400,
            'tax_amount' => 0,
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('purchases.confirm', $purchase));
            $this->fail('在庫レコードが不足しているため確定処理は失敗する必要があります。');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => Purchase::STATUS_DRAFT]);
            $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 0]);
            $this->assertDatabaseMissing('stock_movements', ['reference_id' => $purchase->id]);
        }
    }

    /**
     * @param  array<string, int|string>  $overrides
     * @return array{User, Purchase, Product}
     */
    private function createPurchase(array $overrides = []): array
    {
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'code' => fake()->unique()->bothify('SUP-###'),
            'name' => fake()->company(),
            'is_active' => true,
        ]);
        $category = Category::create(['name' => fake()->unique()->word(), 'is_active' => true]);
        $product = $this->createProduct($category, fake()->unique()->bothify('PRO-###'));
        Stock::create([
            'product_id' => $product->id,
            'quantity' => $overrides['stock_quantity'] ?? 0,
            'average_cost' => $overrides['stock_average_cost'] ?? 0,
        ]);
        $purchase = Purchase::create([
            'purchase_number' => fake()->unique()->bothify('PUR-######'),
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-28',
            'status' => Purchase::STATUS_DRAFT,
            'subtotal' => 800,
            'tax_amount' => 0,
            'total_amount' => 800,
            'created_by' => $user->id,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => $overrides['item_quantity'] ?? 5,
            'unit_price' => $overrides['item_unit_price'] ?? 160,
            'subtotal' => 800,
            'tax_amount' => 0,
        ]);

        return [$user, $purchase, $product];
    }

    private function createProduct(Category $category, string $code): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'code' => $code,
            'name' => fake()->unique()->word(),
            'unit' => '個',
            'standard_sale_price' => 300,
            'reorder_level' => 10,
            'is_active' => true,
        ]);
    }
}
