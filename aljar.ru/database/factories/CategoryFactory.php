<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'type' => ProductType::Coffee,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => Str::ucfirst($name),
            'sort' => 0,
        ];
    }

    public function equipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Equipment,
        ]);
    }
}
