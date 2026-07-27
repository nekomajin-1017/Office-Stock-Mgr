<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
  use RefreshDatabase;

  public function test_authenticated_user_can_register_and_edit_product(): void
  {
    $user = User::factory()->create();
    $category = Category::create(['name' => '文房具', 'is_active' => true]);

    $this->actingAs($user)
      ->post(route('products.store'), $this->productData(['category_id' => $category->id]))
      ->assertRedirect(route('products.index'));

    $product = Product::where('code', 'PEN-001')->firstOrFail();
    $this->assertDatabaseHas('stocks', [
      'product_id' => $product->id,
      'quantity' => 0,
      'average_cost' => 0,
    ]);

    $this->actingAs($user)
      ->put(route('products.update', $product), $this->productData([
        'category_id' => $category->id,
        'name' => '油性ボールペン（青）',
        'is_active' => '0',
      ]))
      ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
      'id' => $product->id,
      'name' => '油性ボールペン（青）',
      'is_active' => false,
    ]);
  }

  public function test_product_list_supports_keyword_category_and_active_status_filters(): void
  {
    $user = User::factory()->create();
    $stationery = Category::create(['name' => '文房具', 'is_active' => true]);
    $paper = Category::create(['name' => '用紙', 'is_active' => true]);
    $pen = $this->createProduct($stationery, ['code' => 'PEN-001', 'name' => 'ボールペン']);
    $paperProduct = $this->createProduct($paper, ['code' => 'PAP-001', 'name' => 'コピー用紙']);
    $inactiveProduct = $this->createProduct($stationery, ['code' => 'PEN-002', 'name' => '赤ペン', 'is_active' => false]);

    $this->actingAs($user)
      ->get(route('products.index', ['keyword' => 'PEN']))
      ->assertSee($pen->name)
      ->assertSee($inactiveProduct->name)
      ->assertDontSee($paperProduct->name);

    $this->actingAs($user)
      ->get(route('products.index', ['category_id' => $paper->id, 'is_active' => '1']))
      ->assertSee($paperProduct->name)
      ->assertDontSee($pen->name)
      ->assertDontSee($inactiveProduct->name);
  }

  public function test_product_list_is_paginated_and_eager_loads_category_and_stock(): void
  {
    $user = User::factory()->create();
    $category = Category::create(['name' => '文房具', 'is_active' => true]);

    foreach (range(1, 11) as $number) {
      $product = $this->createProduct($category, ['code' => sprintf('PEN-%03d', $number)]);
      Stock::create(['product_id' => $product->id, 'quantity' => $number, 'average_cost' => 0]);
    }

    $this->actingAs($user)
      ->get(route('products.index'))
      ->assertOk()
      ->assertViewHas('products', function (LengthAwarePaginator $products): bool {
        return $products->total() === 11
          && $products->count() === 10
          && $products->every(fn (Product $product): bool => $product->relationLoaded('category') && $product->relationLoaded('stock'));
      });
  }

  public function test_inactive_category_cannot_be_selected_for_new_product(): void
  {
    $user = User::factory()->create();
    $category = Category::create(['name' => '無効カテゴリ', 'is_active' => false]);

    $this->actingAs($user)
      ->post(route('products.store'), $this->productData(['category_id' => $category->id]))
      ->assertSessionHasErrors('category_id');
  }

  public function test_product_edit_screen_includes_current_inactive_category(): void
  {
    $user = User::factory()->create();
    $category = Category::create(['name' => '無効カテゴリ', 'is_active' => false]);
    $product = $this->createProduct($category);

    $this->actingAs($user)
      ->get(route('products.edit', $product))
      ->assertOk()
      ->assertSee($category->name);
  }

  public function test_product_detail_displays_stock_information(): void
  {
    $user = User::factory()->create();
    $category = Category::create(['name' => '文房具', 'is_active' => true]);
    $product = $this->createProduct($category, ['code' => 'PEN-001']);
    Stock::create(['product_id' => $product->id, 'quantity' => 5, 'average_cost' => 120]);

    $this->actingAs($user)
      ->get(route('products.show', $product))
      ->assertOk()
      ->assertSee('現在庫')
      ->assertSee('5')
      ->assertSee('120.00 円')
      ->assertSee('600.00 円');
  }

  public function test_product_is_deactivated_without_being_physically_deleted(): void
  {
    $user = User::factory()->create();
    $category = Category::create(['name' => '文房具', 'is_active' => true]);
    $product = $this->createProduct($category);

    $this->actingAs($user)
      ->delete(route('products.destroy', $product))
      ->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
  }

  public function test_inactive_products_are_excluded_from_purchase_and_sale_candidates(): void
  {
    $category = Category::create(['name' => '文房具', 'is_active' => true]);
    $activeProduct = $this->createProduct($category, ['code' => 'PEN-001', 'is_active' => true]);
    $inactiveProduct = $this->createProduct($category, ['code' => 'PEN-002', 'is_active' => false]);

    $products = Product::active()->orderBy('id')->get();

    $this->assertTrue($products->contains($activeProduct));
    $this->assertFalse($products->contains($inactiveProduct));
  }

  private function createProduct(Category $category, array $overrides = []): Product
  {
    return Product::create(array_merge([
      'category_id' => $category->id,
      'code' => fake()->unique()->bothify('CODE-###'),
      'name' => fake()->unique()->word(),
      'unit' => '個',
      'standard_sale_price' => 100,
      'reorder_level' => 10,
      'is_active' => true,
    ], $overrides));
  }

  /**
   * @param array<string, int|string> $overrides
   * @return array<string, int|string>
   */
  private function productData(array $overrides = []): array
  {
    return array_merge([
      'category_id' => 0,
      'code' => 'PEN-001',
      'name' => '油性ボールペン',
      'unit' => '本',
      'standard_sale_price' => '120',
      'reorder_level' => '10',
      'description' => 'テスト商品です。',
      'is_active' => '1',
    ], $overrides);
  }
}
