<?php

namespace Database\Factories;

use App\Enums\Grind;
use App\Enums\ProductType;
use App\Enums\Roast;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Строка хранит снимок товара на момент покупки — название, вес и цену.
     * По умолчанию поля заполняются произвольно; чтобы снимок совпал с
     * реальным вариантом из каталога, используйте состояние forVariant().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_variant_id' => null,
            'product_name' => fake()->words(2, true),
            'variant_title' => fake()->randomElement(['250 г', '500 г', '1 кг']),
            'weight_g' => fake()->randomElement([250, 500, 1000]),
            'unit_price' => fake()->numberBetween(50000, 120000),
            'grind' => fake()->randomElement(Grind::cases()),
            'roast' => fake()->randomElement(Roast::cases()),
            'subscribe' => fake()->boolean(20),
            'qty' => fake()->numberBetween(1, 3),
        ];
    }

    /**
     * Снимок конкретного варианта из каталога: строка заказа перестаёт
     * расходиться с товаром, на который ссылается.
     *
     * Помол проставляется только кофе — у турки и гейзерной кофеварки
     * его не бывает, колонка на этот случай и сделана нулевой.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_title' => $variant->title,
            'weight_g' => $variant->weight_g,
            'unit_price' => $variant->price,
            'grind' => $variant->product->type === ProductType::Coffee
                ? fake()->randomElement(Grind::cases())
                : null,
            'roast' => $variant->product->type === ProductType::Coffee
                ? fake()->randomElement(Roast::cases())
                : null,
        ]);
    }
}
