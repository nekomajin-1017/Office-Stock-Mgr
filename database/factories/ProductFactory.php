<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'code' => fake()->unique()->bothify('PRO-#####'),
            'name' => fake()->words(2, true),
            'unit' => '個',
            'standard_sale_price' => fake()->numberBetween(0, 100000),
            'reorder_level' => fake()->numberBetween(0, 100),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
