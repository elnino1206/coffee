<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
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
            'provider' => (string) config('delivery.provider'),
            'provider_uuid' => fake()->uuid(),
            'track_number' => (string) fake()->unique()->numberBetween(1_000_000_000, 9_999_999_999),
            'tariff_code' => (int) config('delivery.cdek.tariffs.pickup'),
            'status' => ShipmentStatus::Created,
            'status_at' => now(),
            'cost' => fake()->numberBetween(20_000, 60_000),
            'payload' => null,
        ];
    }

    public function status(ShipmentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'status_at' => now(),
        ]);
    }
}
