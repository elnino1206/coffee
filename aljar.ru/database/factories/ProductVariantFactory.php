<?php

namespace Database\Factories;

use App\Models\Coffee;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Coffee::factory(),
            'title' => '250 г',
            // Цена в копейках, как и везде в проекте.
            'price' => fake()->numberBetween(50_000, 200_000),
            'sku' => fake()->unique()->bothify('AJ-####'),
            'stock' => fake()->numberBetween(0, 40),
            'weight_g' => 250,
            'volume_ml' => null,
            'is_active' => true,
            'sort' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
