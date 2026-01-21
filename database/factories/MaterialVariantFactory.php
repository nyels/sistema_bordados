<?php

namespace Database\Factories;

use App\Models\MaterialVariant;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialVariantFactory extends Factory
{
    protected $model = MaterialVariant::class;

    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'name' => fake()->colorName(),
            'sku_variant' => fake()->unique()->bothify('VAR-####'),
            'current_stock' => fake()->numberBetween(0, 100),
            'average_cost' => fake()->randomFloat(2, 10, 100),
            'current_value' => fake()->randomFloat(2, 100, 1000),
            'is_active' => true,
        ];
    }
}
