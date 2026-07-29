<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SUP-#####'),
            'name' => fake()->company(),
            'postal_code' => fake()->postcode(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'contact_person' => fake()->name(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
