<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'sku' => fake()->unique()->bothify('PROD-####'),
            'base_price' => fake()->randomFloat(2, 50, 500),
            'production_lead_time' => fake()->numberBetween(3, 15),
            'is_active' => true,
        ];
    }
}
