<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PayMethod;
use App\Enums\ShipMethod;
use App\Models\Customer;
use App\Models\NumberSequence;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Номер берётся из той же последовательности, что и у боевого заказа,
     * а не из случайного числа: колонка уникальна, и выдуманный номер рано
     * или поздно совпал бы с настоящим.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ship = fake()->randomElement(ShipMethod::cases());

        return [
            'number' => NumberSequence::next('order'),
            'customer_id' => Customer::factory(),
            'contact_name' => fake()->name(),
            'phone' => '+79'.fake()->numerify('#########'),
            'email' => fake()->boolean(60) ? fake()->safeEmail() : null,
            'ship_method' => $ship,
            'address' => $ship === ShipMethod::Pickup ? null : fake()->address(),
            'delivery_price' => $ship === ShipMethod::Courier ? fake()->randomElement([0, 35000]) : 0,
            'pay_method' => fake()->randomElement(PayMethod::cases()),
            'comment' => fake()->boolean(25) ? fake()->sentence() : null,
            'status' => OrderStatus::New,
            'placed_at' => fake()->dateTimeBetween('-3 months'),
        ];
    }

    /**
     * Заказ без учётной записи: оформлен по телефону, гостем.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
        ]);
    }

    /**
     * Заказ в заданном состоянии.
     */
    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
