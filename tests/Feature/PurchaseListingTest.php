<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PurchaseListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_list_is_displayed_with_eager_loaded_supplier_and_creator(): void
    {
        [$user, $purchase] = $this->createPurchase('PUR-001', '2026-07-20', Purchase::STATUS_DRAFT);

        $this->actingAs($user)->get(route('purchases.index'))
            ->assertOk()->assertSee($purchase->purchase_number)->assertSee($purchase->supplier->name)
            ->assertViewHas('purchases', fn (LengthAwarePaginator $purchases) => $purchases->every(fn (Purchase $purchase) => $purchase->relationLoaded('supplier') && $purchase->relationLoaded('creator')));
    }

    public function test_purchase_list_can_be_filtered(): void
    {
        [$user, $first] = $this->createPurchase('PUR-001', '2026-07-10', Purchase::STATUS_DRAFT);
        [, $second] = $this->createPurchase('PUR-002', '2026-07-20', Purchase::STATUS_CONFIRMED, $user);

        $this->actingAs($user)->get(route('purchases.index', ['purchase_number' => '002', 'supplier_id' => $second->supplier_id, 'date_from' => '2026-07-15', 'date_to' => '2026-07-25', 'status' => Purchase::STATUS_CONFIRMED]))
            ->assertSee($second->purchase_number)->assertDontSee('2026/07/10');
    }

    public function test_purchase_detail_displays_header_items_and_total(): void
    {
        [$user, $purchase, $product] = $this->createPurchase('PUR-001', '2026-07-20', Purchase::STATUS_DRAFT);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 150, 'subtotal' => 300, 'tax_amount' => 0]);
        $purchase->update(['subtotal' => 300, 'total_amount' => 300]);

        $this->actingAs($user)->get(route('purchases.show', $purchase))
            ->assertOk()->assertSee($purchase->supplier->name)->assertSee($product->name)->assertSee('300 円')->assertSee('一覧へ戻る');
    }

    public function test_purchase_list_preserves_search_query_in_pagination(): void
    {
        [$user] = $this->createPurchase('PUR-001', '2026-07-20', Purchase::STATUS_DRAFT);
        $this->actingAs($user)->get(route('purchases.index', ['status' => Purchase::STATUS_DRAFT]))
            ->assertOk()->assertSee('PUR-001');
    }

    private function createPurchase(string $number, string $date, string $status, ?User $user = null): array
    {
        $user ??= User::factory()->create();
        $supplier = Supplier::create(['code' => 'SUP-'.$number, 'name' => '仕入先'.$number, 'is_active' => true]);
        $category = Category::firstOrCreate(['name' => '文房具'], ['is_active' => true]);
        $product = Product::firstOrCreate(['code' => 'PRO-'.$number], ['category_id' => $category->id, 'supplier_id' => $supplier->id, 'name' => '商品'.$number, 'unit' => '個', 'standard_sale_price' => 100, 'reorder_level' => 1, 'is_active' => true]);
        $purchase = Purchase::create(['purchase_number' => $number, 'supplier_id' => $supplier->id, 'purchase_date' => $date, 'status' => $status, 'subtotal' => 0, 'tax_amount' => 0, 'total_amount' => 0, 'created_by' => $user->id]);

        return [$user, $purchase, $product];
    }
}
