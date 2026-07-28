<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PurchaseRegistrationTest extends TestCase
{
  use RefreshDatabase;

  public function test_purchase_create_screen_is_displayed(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();

    $this->actingAs($user)->get(route('purchases.create'))
      ->assertOk()->assertSee($supplier->name)->assertSee($product->name)->assertSee('明細を追加');
  }

  public function test_inactive_supplier_and_product_are_excluded_from_candidates(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();
    $inactiveSupplier = Supplier::create(['code' => 'SUP-002', 'name' => '無効仕入先', 'is_active' => false]);
    $inactiveProduct = $this->createProduct($product->category, 'P-002', '無効商品', false);

    $this->actingAs($user)->get(route('purchases.create'))
      ->assertSee($supplier->name)->assertSee($product->name)
      ->assertDontSee($inactiveSupplier->name)->assertDontSee($inactiveProduct->name);
  }

  public function test_purchase_requires_at_least_one_item(): void
  {
    [$user, $supplier] = $this->createCandidates();

    $this->actingAs($user)->post(route('purchases.store'), ['supplier_id' => $supplier->id, 'purchase_date' => '2026-07-28', 'items' => []])
      ->assertSessionHasErrors('items');
  }

  public function test_purchase_rejects_invalid_quantity_and_unit_price(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();

    $this->actingAs($user)->post(route('purchases.store'), $this->payload($supplier, $product, 0, -1))
      ->assertSessionHasErrors(['items.0.quantity', 'items.0.unit_price']);
  }

  public function test_purchase_rejects_inactive_product(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();
    $inactiveProduct = $this->createProduct($product->category, 'P-002', '無効商品', false);

    $this->actingAs($user)->post(route('purchases.store'), $this->payload($supplier, $inactiveProduct))
      ->assertSessionHasErrors('items.0.product_id');
  }

  public function test_purchase_is_registered_as_draft(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();

    $this->actingAs($user)->post(route('purchases.store'), $this->payload($supplier, $product))
      ->assertRedirect();

    $this->assertDatabaseHas('purchases', ['supplier_id' => $supplier->id, 'status' => 'draft', 'created_by' => $user->id]);
    $this->assertDatabaseHas('purchase_items', ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]);
  }

  public function test_purchase_can_register_multiple_items_and_calculates_total(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();
    $secondProduct = $this->createProduct($product->category, 'P-002', '商品2', true);
    Stock::create(['product_id' => $secondProduct->id, 'quantity' => 0, 'average_cost' => 0]);
    $payload = $this->payload($supplier, $product, 2, 150);
    $payload['items'][] = ['product_id' => $secondProduct->id, 'quantity' => 3, 'unit_price' => 200];

    $this->actingAs($user)->post(route('purchases.store'), $payload);

    $purchase = \App\Models\Purchase::firstOrFail();
    $this->assertSame('900.00', $purchase->fresh()->total_amount);
    $this->assertCount(2, $purchase->items);
  }

  public function test_draft_purchase_does_not_change_stock(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();

    $this->actingAs($user)->post(route('purchases.store'), $this->payload($supplier, $product, 5, 100));

    $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 0, 'average_cost' => 0]);
  }

  public function test_transaction_rolls_back_when_item_creation_fails(): void
  {
    [$user, $supplier, $product] = $this->createCandidates();
    PurchaseItem::creating(fn () => throw new RuntimeException('forced failure'));
    $this->withoutExceptionHandling();

    try {
      $this->actingAs($user)->post(route('purchases.store'), $this->payload($supplier, $product));
      $this->fail('明細作成失敗時は例外になる必要があります。');
    } catch (RuntimeException) {
      $this->assertDatabaseCount('purchases', 0);
      $this->assertDatabaseCount('purchase_items', 0);
    } finally {
      PurchaseItem::flushEventListeners();
    }
  }

  private function createCandidates(): array
  {
    $user = User::factory()->create();
    $supplier = Supplier::create(['code' => 'SUP-001', 'name' => '有効仕入先', 'is_active' => true]);
    $category = Category::create(['name' => '文房具', 'is_active' => true]);
    $product = $this->createProduct($category, 'P-001', '有効商品', true);
    Stock::create(['product_id' => $product->id, 'quantity' => 0, 'average_cost' => 0]);

    return [$user, $supplier, $product];
  }

  private function createProduct(Category $category, string $code, string $name, bool $active): Product
  {
    return Product::create(['category_id' => $category->id, 'code' => $code, 'name' => $name, 'unit' => '個', 'standard_sale_price' => 100, 'reorder_level' => 1, 'is_active' => $active]);
  }

  private function payload(Supplier $supplier, Product $product, int $quantity = 1, int $unitPrice = 100): array
  {
    return ['supplier_id' => $supplier->id, 'purchase_date' => '2026-07-28', 'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $unitPrice]]];
  }
}
