<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
  use RefreshDatabase;

  public function test_admin_can_access_user_management_screen(): void
  {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
      ->get(route('users.index'))
      ->assertOk()
      ->assertSee('ユーザー管理');
  }

  public function test_general_user_cannot_access_user_management_screen(): void
  {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
      ->get(route('users.index'))
      ->assertForbidden();
  }

  public function test_guest_is_redirected_to_login_before_admin_authorization(): void
  {
    $this->get(route('users.index'))
      ->assertRedirect(route('login'));
  }

  public function test_admin_navigation_is_only_visible_to_admin(): void
  {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($admin)
      ->get(route('products.index'))
      ->assertSee('ユーザー管理');

    $this->actingAs($user)
      ->get(route('products.index'))
      ->assertDontSee('ユーザー管理');
  }

  public function test_user_role_helper_methods_return_expected_results(): void
  {
    $admin = User::factory()->make(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->make(['role' => User::ROLE_USER]);

    $this->assertTrue($admin->isAdmin());
    $this->assertFalse($admin->isUser());
    $this->assertTrue($user->isUser());
    $this->assertFalse($user->isAdmin());
  }

  public function test_user_management_policy_allows_only_admin(): void
  {
    $admin = User::factory()->make(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->make(['role' => User::ROLE_USER]);

    $this->assertTrue($admin->can('viewAny', User::class));
    $this->assertFalse($user->can('viewAny', User::class));
  }
}
