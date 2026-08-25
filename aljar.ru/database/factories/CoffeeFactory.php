<?php

namespace Database\Factories;

use App\Enums\BrewMethod;
use App\Enums\Roast;
use App\Models\Coffee;
use App\Models\CoffeeDetail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coffee>
 */
class CoffeeFactory extends Factory
{
    protected $model = Coffee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->word()).' '.Str::ucfirst(fake()->word());

        return [
            'category_id' => null,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => $name,
            'full_name' => 'Арабика 100% '.$name,
            'image_path' => 'img/bag-kraft.webp',
            'is_featured' => false,
            'is_hidden' => false,
            'wholesale_available' => false,
        ];
    }

    /**
     * Кофе без характеристик не бывает: деталь заводится вместе с товаром.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Coffee $coffee): void {
            CoffeeDetail::query()->create([
                'product_id' => $coffee->id,
                'species' => '100% арабика',
                'origin' => fake()->randomElement(['Бразилия', 'Колумбия', 'Эфиопия']),
                'region' => fake()->word(),
                'process' => fake()->randomElement(['Мытый', 'Натуральный']),
                'notes' => 'Шоколад, орех, карамель',
                'roast' => fake()->randomElement(Roast::cases()),
                'brew_method' => fake()->randomElement(BrewMethod::cases()),
                'roast_date' => now()->subDays(fake()->numberBetween(0, 12)),
                'profile_fruity' => fake()->numberBetween(0, 100),
                'profile_chocolate' => fake()->numberBetween(0, 100),
                'profile_spice' => fake()->numberBetween(0, 100),
                'profile_body' => fake()->numberBetween(0, 100),
                'profile_acidity' => fake()->numberBetween(0, 100),
            ]);
        });
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
