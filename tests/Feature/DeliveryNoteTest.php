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

class DeliveryNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_sale_can_be_downloaded_as_a_delivery_note_pdf(): void
    {
        [$user, $sale] = $this->createSale(Sale::STATUS_CONFIRMED);

        $this->actingAs($user)
            ->get(route('sales.delivery-note', $sale))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=delivery-note-{$sale->sale_number}.pdf");
    }

    public function test_draft_sale_cannot_be_downloaded_as_a_delivery_note(): void
    {
        [$user, $sale] = $this->createSale(Sale::STATUS_DRAFT);

        $this->actingAs($user)
            ->get(route('sales.delivery-note', $sale))
            ->assertForbidden();
    }

    private function createSale(string $status): array
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'code' => 'CUS-001',
            'name' => '株式会社テスト',
            'postal_code' => '100-0001',
            'address' => '東京都千代田区テスト1-1-1',
            'is_active' => true,
        ]);
        $supplier = Supplier::create(['code' => 'SUP-001', 'name' => '仕入先', 'is_active' => true]);
        $category = Category::create(['name' => '文房具', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'code' => 'PRO-001',
            'name' => 'ボールペン',
            'unit' => '本',
            'standard_sale_price' => 120,
            'reorder_level' => 3,
            'is_active' => true,
        ]);
        Stock::create(['product_id' => $product->id, 'quantity' => 10, 'average_cost' => 80]);
        $sale = Sale::create([
            'sale_number' => 'SAL-DELIVERY-001',
            'customer_id' => $customer->id,
            'sale_date' => '2026-07-28',
            'status' => $status,
            'subtotal' => 240,
            'tax_amount' => 0,
            'total_amount' => 240,
            'created_by' => $user->id,
            'confirmed_at' => $status === Sale::STATUS_CONFIRMED ? now() : null,
            'confirmed_by' => $status === Sale::STATUS_CONFIRMED ? $user->id : null,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 120,
            'cost_unit_price' => 80,
            'subtotal' => 240,
            'cost_amount' => 160,
            'tax_amount' => 0,
        ]);

        return [$user, $sale];
    }
}
