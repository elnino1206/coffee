<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => (string) config('payment.provider'),
            'provider_payment_id' => (string) fake()->unique()->numberBetween(1_000_000_000, 9_999_999_999),
            'amount' => fake()->numberBetween(50_000, 500_000),
            'status' => PaymentStatus::New,
            'paid_at' => null,
            'binds_card' => false,
            'payload' => null,
        ];
    }

    /**
     * Удачное списание: только у него есть дата оплаты.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }

    /**
     * Первый платёж по подписке — с привязкой карты.
     */
    public function bindsCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'binds_card' => true,
        ]);
    }
}
