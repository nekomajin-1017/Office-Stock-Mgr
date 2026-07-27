<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
  use RefreshDatabase;

  public function test_admin_can_register_general_user_and_admin(): void
  {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
      ->post(route('users.store'), $this->userData(['email' => 'user@example.com']))
      ->assertRedirect(route('users.index'));

    $this->actingAs($admin)
      ->post(route('users.store'), $this->userData([
        'email' => 'new-admin@example.com',
        'role' => User::ROLE_ADMIN,
      ]))
      ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', ['email' => 'user@example.com', 'role' => User::ROLE_USER]);
    $this->assertDatabaseHas('users', ['email' => 'new-admin@example.com', 'role' => User::ROLE_ADMIN]);
  }

  public function test_admin_can_edit_user_role_and_active_status(): void
  {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER, 'is_active' => true]);

    $this->actingAs($admin)
      ->put(route('users.update', $user), $this->userData([
        'email' => $user->email,
        'role' => User::ROLE_ADMIN,
        'is_active' => '0',
        'password' => '',
        'password_confirmation' => '',
      ]))
      ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
      'id' => $user->id,
      'role' => User::ROLE_ADMIN,
      'is_active' => false,
    ]);
  }

  public function test_last_active_admin_cannot_be_deactivated_or_demoted(): void
  {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    $anotherAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->actingAs($anotherAdmin)
      ->put(route('users.update', $admin), $this->userData([
        'email' => $admin->email,
        'role' => User::ROLE_USER,
        'password' => '',
        'password_confirmation' => '',
      ]))
      ->assertRedirect(route('users.index'));

    $this->actingAs($anotherAdmin)
      ->put(route('users.update', $anotherAdmin), $this->userData([
        'email' => $anotherAdmin->email,
        'role' => User::ROLE_USER,
        'password' => '',
        'password_confirmation' => '',
      ]))
      ->assertSessionHasErrors('role');

    $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id, 'role' => User::ROLE_ADMIN]);
  }

  public function test_admin_cannot_deactivate_or_demote_self(): void
  {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

    $this->actingAs($admin)
      ->put(route('users.update', $admin), $this->userData([
        'email' => $admin->email,
        'is_active' => '0',
        'password' => '',
        'password_confirmation' => '',
      ]))
      ->assertSessionHasErrors('role');

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
  }

  public function test_general_user_cannot_manage_users(): void
  {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
      ->get(route('users.create'))
      ->assertForbidden();
  }

  /**
   * @param array<string, string> $overrides
   * @return array<string, string>
   */
  private function userData(array $overrides = []): array
  {
    return array_merge([
      'name' => '登録ユーザー',
      'email' => 'default@example.com',
      'password' => 'password123',
      'password_confirmation' => 'password123',
      'role' => User::ROLE_USER,
      'is_active' => '1',
    ], $overrides);
  }
}
