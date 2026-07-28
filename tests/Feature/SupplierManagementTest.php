<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
  use RefreshDatabase;

  public function test_supplier_list_is_displayed_and_can_be_filtered(): void
  {
    $user = User::factory()->create();
    $activeSupplier = Supplier::create($this->supplierData(['code' => 'SUP-001', 'name' => '文具商事']));
    $inactiveSupplier = Supplier::create($this->supplierData([
      'code' => 'SUP-002',
      'name' => '用紙販売',
      'is_active' => false,
    ]));

    $this->actingAs($user)
      ->get(route('suppliers.index', ['keyword' => 'SUP-001', 'is_active' => '1']))
      ->assertOk()
      ->assertSee($activeSupplier->name)
      ->assertDontSee($inactiveSupplier->name);
  }

  public function test_authenticated_user_can_store_and_update_supplier(): void
  {
    $user = User::factory()->create();

    $this->actingAs($user)
      ->post(route('suppliers.store'), $this->supplierData())
      ->assertRedirect(route('suppliers.index'));

    $supplier = Supplier::where('code', 'SUP-001')->firstOrFail();

    $this->actingAs($user)
      ->put(route('suppliers.update', $supplier), $this->supplierData([
        'name' => '文具商事株式会社',
        'email' => 'updated@example.com',
      ]))
      ->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', [
      'id' => $supplier->id,
      'name' => '文具商事株式会社',
      'email' => 'updated@example.com',
    ]);
  }

  public function test_supplier_code_must_be_unique(): void
  {
    $user = User::factory()->create();
    Supplier::create($this->supplierData());

    $this->actingAs($user)
      ->post(route('suppliers.store'), $this->supplierData())
      ->assertSessionHasErrors('code');
  }

  public function test_supplier_can_be_deactivated_and_reactivated_without_deletion(): void
  {
    $user = User::factory()->create();
    $supplier = Supplier::create($this->supplierData());

    $this->actingAs($user)
      ->patch(route('suppliers.toggle-status', $supplier))
      ->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);

    $this->actingAs($user)
      ->patch(route('suppliers.toggle-status', $supplier))
      ->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => true]);
  }

  public function test_inactive_supplier_is_excluded_from_new_purchase_candidates(): void
  {
    $activeSupplier = Supplier::create($this->supplierData(['code' => 'SUP-001']));
    $inactiveSupplier = Supplier::create($this->supplierData(['code' => 'SUP-002', 'is_active' => false]));

    $suppliers = Supplier::active()->orderBy('id')->get();

    $this->assertTrue($suppliers->contains($activeSupplier));
    $this->assertFalse($suppliers->contains($inactiveSupplier));
  }

  public function test_deactivating_supplier_keeps_purchase_history_relation(): void
  {
    $user = User::factory()->create();
    $supplier = Supplier::create($this->supplierData());
    $purchase = Purchase::create([
      'purchase_number' => 'PUR-001',
      'supplier_id' => $supplier->id,
      'purchase_date' => '2026-07-01',
      'status' => 'draft',
      'subtotal' => 0,
      'tax_amount' => 0,
      'total_amount' => 0,
      'created_by' => $user->id,
    ]);

    $this->actingAs($user)
      ->patch(route('suppliers.toggle-status', $supplier))
      ->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('purchases', [
      'id' => $purchase->id,
      'supplier_id' => $supplier->id,
    ]);
  }

  /**
   * @param array<string, bool|string> $overrides
   * @return array<string, bool|string>
   */
  private function supplierData(array $overrides = []): array
  {
    return array_merge([
      'code' => 'SUP-001',
      'name' => '文具商事',
      'postal_code' => '100-0001',
      'address' => '東京都千代田区1-1-1',
      'phone' => '03-1234-5678',
      'email' => 'supplier@example.com',
      'contact_person' => '山田太郎',
      'is_active' => '1',
    ], $overrides);
  }
}
