<?php

namespace Database\Factories;

use App\Enums\Grind;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'SUB-'.fake()->unique()->numberBetween(100, 9999),
            'customer_id' => Customer::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'grind' => Grind::Whole,
            'frequency_weeks' => 2,
            'next_delivery_at' => now()->addWeeks(2)->toDateString(),
            'status' => SubscriptionStatus::Active,
            // Процент, а не доля: в подписке он зафиксирован числом.
            'discount_percent' => (int) config('subscription.default_discount_percent'),
            'failed_charge_count' => 0,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Paused,
            'paused_at' => now(),
            // Отгрузки нет — и даты быть не должно: иначе строка обещает
            // привезти кофе по остановленной подписке.
            'next_delivery_at' => null,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Blocked,
            'blocked_at' => now(),
            'next_delivery_at' => null,
            'failed_charge_count' => 3,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'next_delivery_at' => null,
        ]);
    }
}
