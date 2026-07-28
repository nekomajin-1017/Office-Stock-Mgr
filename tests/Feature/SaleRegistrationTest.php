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
use Tests\TestCase;
use RuntimeException;

class SaleRegistrationTest extends TestCase
{
  use RefreshDatabase;

  public function test_sale_create_screen_is_displayed(): void
  {
    [$user, $customer, $product] = $this->candidates();
    $this->actingAs($user)->get(route('sales.create'))->assertOk()->assertSee($customer->name)->assertSee($product->name);
  }

  public function test_inactive_customer_and_product_are_excluded(): void
  {
    [$user, $customer, $product] = $this->candidates();
    $inactiveCustomer = Customer::create(['code' => 'C2', 'name' => '無効顧客', 'is_active' => false]);
    $inactiveProduct = $this->product($product->category, 'P2', '無効商品', false);
    $this->actingAs($user)->get(route('sales.create'))->assertSee($customer->name)->assertSee($product->name)->assertDontSee($inactiveCustomer->name)->assertDontSee($inactiveProduct->name);
  }

  public function test_sale_requires_items(): void
  {
    [$user, $customer] = $this->candidates();
    $this->actingAs($user)->post(route('sales.store'), ['customer_id' => $customer->id, 'sale_date' => '2026-07-28', 'items' => []])->assertSessionHasErrors('items');
  }

  public function test_sale_rejects_invalid_quantity_and_price(): void
  {
    [$user, $customer, $product] = $this->candidates();
    $this->actingAs($user)->post(route('sales.store'), $this->payload($customer, $product, 0, -1))->assertSessionHasErrors(['items.0.quantity', 'items.0.unit_price']);
  }

  public function test_sale_rejects_inactive_product(): void
  {
    [$user, $customer, $product] = $this->candidates(); $inactive = $this->product($product->category, 'P2', '無効商品', false);
    $this->actingAs($user)->post(route('sales.store'), $this->payload($customer, $inactive))->assertSessionHasErrors('items.0.product_id');
  }

  public function test_sale_is_registered_as_draft(): void
  {
    [$user, $customer, $product] = $this->candidates();
    $this->actingAs($user)->post(route('sales.store'), $this->payload($customer, $product))->assertRedirect();
    $this->assertDatabaseHas('sales', ['customer_id' => $customer->id, 'status' => 'draft', 'created_by' => $user->id]);
  }

  public function test_sale_can_register_multiple_items_and_calculates_total(): void
  {
    [$user, $customer, $product] = $this->candidates(); $second = $this->product($product->category, 'P2', '商品2', true); Stock::create(['product_id' => $second->id, 'quantity' => 10, 'average_cost' => 0]);
    $payload = $this->payload($customer, $product, 2, 150); $payload['items'][] = ['product_id' => $second->id, 'quantity' => 3, 'unit_price' => 200];
    $this->actingAs($user)->post(route('sales.store'), $payload);
    $sale = Sale::firstOrFail(); $this->assertSame('900.00', $sale->fresh()->total_amount); $this->assertCount(2, $sale->items);
  }

  public function test_draft_sale_does_not_change_stock(): void
  {
    [$user, $customer, $product] = $this->candidates(); $this->actingAs($user)->post(route('sales.store'), $this->payload($customer, $product, 5));
    $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'quantity' => 10]);
  }

  public function test_transaction_rolls_back_when_item_creation_fails(): void
  {
    [$user, $customer, $product] = $this->candidates(); SaleItem::creating(fn () => throw new RuntimeException('forced'));
    $this->withoutExceptionHandling();
    try { $this->actingAs($user)->post(route('sales.store'), $this->payload($customer, $product)); $this->fail('例外が必要です。'); }
    catch (RuntimeException) { $this->assertDatabaseCount('sales', 0); $this->assertDatabaseCount('sale_items', 0); }
    finally { SaleItem::flushEventListeners(); }
  }

  private function candidates(): array
  {
    $user = User::factory()->create(); $customer = Customer::create(['code' => 'C1', 'name' => '有効顧客', 'is_active' => true]); $category = Category::create(['name' => '文房具', 'is_active' => true]); $product = $this->product($category, 'P1', '有効商品', true); Stock::create(['product_id' => $product->id, 'quantity' => 10, 'average_cost' => 0]); return [$user, $customer, $product];
  }
  private function product(Category $category, string $code, string $name, bool $active): Product { return Product::create(['category_id' => $category->id, 'code' => $code, 'name' => $name, 'unit' => '個', 'standard_sale_price' => 100, 'reorder_level' => 1, 'is_active' => $active]); }
  private function payload(Customer $customer, Product $product, int $quantity = 1, int $price = 100): array { return ['customer_id' => $customer->id, 'sale_date' => '2026-07-28', 'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $price]]]; }
}
