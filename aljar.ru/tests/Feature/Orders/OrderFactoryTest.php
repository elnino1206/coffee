<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\Coffee;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_takes_numbers_from_the_shared_sequence()
    {
        $first = Order::factory()->create();
        $second = Order::factory()->create();

        $start = config('checkout.numbers.order.start');
        $prefix = config('checkout.numbers.order.prefix');

        $this->assertSame($prefix.$start, $first->number);
        $this->assertSame($prefix.($start + 1), $second->number);
    }

    public function test_guest_order_has_no_customer()
    {
        $order = Order::factory()->guest()->create();

        $this->assertNull($order->customer_id);
        $this->assertNotNull($order->contact_name);
    }

    public function test_status_state_sets_the_requested_status()
    {
        $order = Order::factory()->status(OrderStatus::Done)->create();

        $this->assertSame(OrderStatus::Done, $order->status);
    }

    public function test_line_snapshots_the_variant_it_points_at()
    {
        $coffee = Coffee::factory()->create(['name' => 'Pink Bourbon']);
        $variant = $coffee->variants()->create(['title' => '250 г', 'price' => 70_000, 'weight_g' => 250]);

        $line = OrderLine::factory()->forVariant($variant)->create(['qty' => 2]);

        $this->assertSame($variant->id, $line->product_variant_id);
        $this->assertSame('Pink Bourbon', $line->product_name);
        $this->assertSame('250 г', $line->variant_title);
        $this->assertSame(250, $line->weight_g);
        $this->assertSame(70_000, $line->unit_price);
        $this->assertNotNull($line->grind);
    }

    public function test_equipment_line_has_no_grind()
    {
        $equipment = Equipment::factory()->create();
        $variant = $equipment->variants()->create(['title' => '350 мл', 'price' => 420_000]);

        $this->assertSame(ProductType::Equipment, $variant->product->type);

        $line = OrderLine::factory()->forVariant($variant)->create();

        $this->assertNull($line->grind);
    }

    public function test_order_total_adds_delivery_to_the_lines()
    {
        $order = Order::factory()->create(['delivery_price' => 35_000]);

        OrderLine::factory()->create(['order_id' => $order->id, 'unit_price' => 70_000, 'qty' => 2]);
        OrderLine::factory()->create(['order_id' => $order->id, 'unit_price' => 50_000, 'qty' => 1]);

        $order->load('lines');

        $this->assertSame(190_000, $order->goodsTotal());
        $this->assertSame(225_000, $order->total());
    }
}
