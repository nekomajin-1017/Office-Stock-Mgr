<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_category_from_index_screen(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post(route('categories.store'), ['name' => '文房具', 'is_active' => '1'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['name' => '文房具', 'is_active' => true]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Category::create(['name' => '文房具', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('categories.store'), ['name' => '文房具', 'is_active' => '1'])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_deactivate_and_reactivate_category(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::create(['name' => '文房具', 'is_active' => true]);

        $this->actingAs($admin)
            ->put(route('categories.update', $category), ['name' => '文房具', 'is_active' => '0'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => false]);

        $this->actingAs($admin)
            ->put(route('categories.update', $category), ['name' => '文房具', 'is_active' => '1'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => true]);
    }

    public function test_inactive_categories_are_excluded_from_product_category_candidates(): void
    {
        $activeCategory = Category::create(['name' => '有効カテゴリ', 'is_active' => true]);
        $inactiveCategory = Category::create(['name' => '無効カテゴリ', 'is_active' => false]);

        $categories = Category::active()->orderBy('id')->get();

        $this->assertTrue($categories->contains($activeCategory));
        $this->assertFalse($categories->contains($inactiveCategory));
    }

    public function test_general_user_cannot_manage_categories(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertForbidden();
    }
}
