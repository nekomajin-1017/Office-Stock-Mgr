<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
  use RefreshDatabase;

  public function test_customer_list_is_displayed_and_can_be_filtered(): void
  {
    $user = User::factory()->create();
    $activeCustomer = Customer::create($this->customerData(['code' => 'CUS-001', 'name' => '文具株式会社']));
    $inactiveCustomer = Customer::create($this->customerData([
      'code' => 'CUS-002',
      'name' => '用紙販売株式会社',
      'is_active' => false,
    ]));

    $this->actingAs($user)
      ->get(route('customers.index', ['keyword' => 'CUS-001', 'is_active' => '1']))
      ->assertOk()
      ->assertSee($activeCustomer->name)
      ->assertDontSee($inactiveCustomer->name);
  }

  public function test_authenticated_user_can_store_and_update_customer(): void
  {
    $user = User::factory()->create();

    $this->actingAs($user)
      ->post(route('customers.store'), $this->customerData())
      ->assertRedirect(route('customers.index'));

    $customer = Customer::where('code', 'CUS-001')->firstOrFail();

    $this->actingAs($user)
      ->put(route('customers.update', $customer), $this->customerData([
        'name' => '文具株式会社 東京支店',
        'email' => 'updated@example.com',
      ]))
      ->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', [
      'id' => $customer->id,
      'name' => '文具株式会社 東京支店',
      'email' => 'updated@example.com',
    ]);
  }

  public function test_customer_code_must_be_unique(): void
  {
    $user = User::factory()->create();
    Customer::create($this->customerData());

    $this->actingAs($user)
      ->post(route('customers.store'), $this->customerData())
      ->assertSessionHasErrors('code');
  }

  public function test_customer_can_be_deactivated_and_reactivated_without_deletion(): void
  {
    $user = User::factory()->create();
    $customer = Customer::create($this->customerData());

    $this->actingAs($user)
      ->patch(route('customers.toggle-status', $customer))
      ->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);

    $this->actingAs($user)
      ->patch(route('customers.toggle-status', $customer))
      ->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => true]);
  }

  public function test_inactive_customer_is_excluded_from_new_sale_candidates(): void
  {
    $activeCustomer = Customer::create($this->customerData(['code' => 'CUS-001']));
    $inactiveCustomer = Customer::create($this->customerData(['code' => 'CUS-002', 'is_active' => false]));

    $customers = Customer::active()->orderBy('id')->get();

    $this->assertTrue($customers->contains($activeCustomer));
    $this->assertFalse($customers->contains($inactiveCustomer));
  }

  public function test_deactivating_customer_keeps_sale_history_relation(): void
  {
    $user = User::factory()->create();
    $customer = Customer::create($this->customerData());
    $sale = Sale::create([
      'sale_number' => 'SAL-001',
      'customer_id' => $customer->id,
      'sale_date' => '2026-07-28',
      'status' => 'draft',
      'subtotal' => 0,
      'tax_amount' => 0,
      'total_amount' => 0,
      'created_by' => $user->id,
    ]);

    $this->actingAs($user)
      ->patch(route('customers.toggle-status', $customer))
      ->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('sales', [
      'id' => $sale->id,
      'customer_id' => $customer->id,
    ]);
  }

  /**
   * @param array<string, bool|string> $overrides
   * @return array<string, bool|string>
   */
  private function customerData(array $overrides = []): array
  {
    return array_merge([
      'code' => 'CUS-001',
      'name' => '文具株式会社',
      'postal_code' => '100-0001',
      'address' => '東京都千代田区1-1-1',
      'phone' => '03-1234-5678',
      'email' => 'customer@example.com',
      'contact_person' => '山田太郎',
      'is_active' => '1',
    ], $overrides);
  }
}
