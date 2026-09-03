<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

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
            'full_name' => null,
            'image_path' => null,
            'is_featured' => false,
            'is_hidden' => false,
            'wholesale_available' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Equipment $equipment): void {
            // Через связь, а не EquipmentDetail::create(): product_id —
            // первичный ключ, в fillable его нет, при массовом присвоении
            // он отбрасывался.
            $equipment->detail()->create([
                'brand' => fake()->company(),
                'material' => fake()->randomElement(['Медь', 'Нержавеющая сталь', 'Алюминий']),
                'country' => 'Россия',
                'warranty_months' => 12,
                'gas_compatible' => true,
                'electric_compatible' => true,
                'induction_compatible' => fake()->boolean(),
            ]);
        });
    }
}
