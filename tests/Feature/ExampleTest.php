<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
  use RefreshDatabase;

  public function test_root_redirects_to_product_list(): void
  {
    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertRedirect(route('products.index'));
  }
}
