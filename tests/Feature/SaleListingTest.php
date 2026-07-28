<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class SaleListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_list_is_displayed_with_eager_loaded_customer_and_creator(): void
    {
        [$user, $sale] = $this->createSale('SAL-001', '2026-07-20', 'draft');

        $this->actingAs($user)->get(route('sales.index'))
            ->assertOk()->assertSee($sale->sale_number)->assertSee($sale->customer->name)
            ->assertViewHas('sales', fn (LengthAwarePaginator $sales) => $sales->every(fn (Sale $sale) => $sale->relationLoaded('customer') && $sale->relationLoaded('creator')));
    }

    public function test_sale_list_can_be_filtered(): void
    {
        [$user] = $this->createSale('SAL-001', '2026-07-10', 'draft');
        [, $sale] = $this->createSale('SAL-002', '2026-07-20', 'confirmed', $user);

        $this->actingAs($user)->get(route('sales.index', ['sale_number' => '002', 'customer_id' => $sale->customer_id, 'date_from' => '2026-07-15', 'date_to' => '2026-07-25', 'status' => 'confirmed']))
            ->assertOk()->assertSee('SAL-002')->assertDontSee('2026/07/10');
    }

    public function test_sale_detail_displays_header_items_and_total(): void
    {
        [$user, $sale, $product] = $this->createSale('SAL-001', '2026-07-20', 'draft');
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 150, 'cost_unit_price' => 100, 'subtotal' => 300, 'cost_amount' => 200, 'tax_amount' => 0]);
        $sale->update(['subtotal' => 300, 'total_amount' => 300]);

        $this->actingAs($user)->get(route('sales.show', $sale))
            ->assertOk()->assertSee($sale->customer->name)->assertSee($product->name)->assertSee('300.00 円')->assertSee('一覧へ戻る');
    }

    private function createSale(string $number, string $date, string $status, ?User $user = null): array
    {
        $user ??= User::factory()->create();
        $customer = Customer::create(['code' => 'CUS-'.$number, 'name' => '顧客'.$number, 'is_active' => true]);
        $category = Category::firstOrCreate(['name' => '文房具'], ['is_active' => true]);
        $product = Product::firstOrCreate(['code' => 'PRO-'.$number], ['category_id' => $category->id, 'name' => '商品'.$number, 'unit' => '個', 'standard_sale_price' => 100, 'reorder_level' => 1, 'is_active' => true]);
        $sale = Sale::create(['sale_number' => $number, 'customer_id' => $customer->id, 'sale_date' => $date, 'status' => $status, 'subtotal' => 0, 'tax_amount' => 0, 'total_amount' => 0, 'created_by' => $user->id]);

        return [$user, $sale, $product];
    }
}
