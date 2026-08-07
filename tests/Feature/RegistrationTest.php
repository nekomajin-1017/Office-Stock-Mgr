<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_screen(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('会員登録')
            ->assertDontSee('name="role"', false);
    }

    public function test_guest_can_register(): void
    {
        $response = $this->post(route('register'), [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('products.index'));
        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue($user->is_active);
    }

    public function test_public_registration_ignores_admin_role(): void
    {
        $this->post(route('register'), [
            'name' => '山田太郎',
            'email' => 'safe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'safe@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'safe@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_registration_requires_valid_input(): void
    {
        User::factory()->create(['email' => 'used@example.com']);

        $this->post(route('register'), [
            'name' => '',
            'email' => 'used@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }
}
