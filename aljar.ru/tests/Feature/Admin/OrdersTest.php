<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PayMethod;
use App\Enums\ShipMethod;
use App\Models\Admin;
use App\Models\Coffee;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function order(array $attributes = []): Order
    {
        $coffee = Coffee::factory()->create(['name' => 'Sidamo']);
        $variant = $coffee->variants()->create(['title' => '250 г', 'price' => 70_000, 'weight_g' => 250]);

        $order = Order::query()->create([
            'number' => 'AJ-'.fake()->unique()->numberBetween(2400, 9999),
            'contact_name' => 'Мария Полякова',
            'phone' => '+7 985 137-92-35',
            'ship_method' => ShipMethod::Courier,
            'pay_method' => PayMethod::Card,
            'status' => OrderStatus::New,
            'placed_at' => now(),
            ...$attributes,
        ]);

        $order->lines()->create([
            'product_variant_id' => $variant->id,
            'product_name' => 'Sidamo',
            'variant_title' => '250 г',
            'weight_g' => 250,
            'unit_price' => 70_000,
            'qty' => 2,
        ]);

        return $order;
    }

    protected function admin(): Admin
    {
        return Admin::factory()->create();
    }

    public function test_orders_are_closed_to_customers()
    {
        $this->actingAs(Customer::factory()->create())
            ->get(route('admin.orders.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_list_shows_orders_with_their_composition()
    {
        $order = $this->order();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Orders')
                ->where('orders.data.0.number', $order->number)
                ->where('orders.data.0.lines.0.name', 'Sidamo')
                ->where('orders.data.0.lines.0.qty', 2)
                ->where('orders.data.0.total', 140_000),
            );
    }

    public function test_orders_can_be_filtered_by_status()
    {
        $this->order(['status' => OrderStatus::New]);
        $done = $this->order(['status' => OrderStatus::Done]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.orders.index', ['status' => 'done']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $done->number),
            );
    }

    public function test_search_finds_an_order_by_phone_and_by_name()
    {
        $order = $this->order(['contact_name' => 'Дмитрий Кузнецов', 'phone' => '+7 906 767-47-25']);
        $this->order(['contact_name' => 'Елена Ким', 'phone' => '+7 903 214-88-10']);

        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.index', ['q' => 'кузнецов']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.number', $order->number),
            );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.index', ['q' => '767-47-25']))
            ->assertInertia(fn (Assert $page) => $page->has('orders.data', 1));
    }

    public function test_status_and_warehouse_comment_can_be_changed()
    {
        $order = $this->order();

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.orders.update', $order->number), [
                'status' => 'work',
                'contact_name' => 'Мария Полякова',
                'phone' => '+7 985 137-92-35',
                'ship_method' => 'pickup',
                'comment' => 'Смолоть под турку',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::Work, $order->status);
        $this->assertSame(ShipMethod::Pickup, $order->ship_method);
        $this->assertSame('Смолоть под турку', $order->comment);
    }

    public function test_composition_and_total_are_not_editable()
    {
        $order = $this->order();

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.orders.update', $order->number), [
                'status' => 'work',
                'phone' => '+7 985 137-92-35',
                'ship_method' => 'courier',
                // Попытка подсунуть состав и сумму мимо формы.
                'total' => 1,
                'lines' => [['product_name' => 'Чужой товар', 'qty' => 99]],
            ]);

        $order->refresh();

        $this->assertSame(140_000, $order->total());
        $this->assertSame(1, $order->lines()->count());
        $this->assertSame('Sidamo', $order->lines()->first()?->product_name);
    }

    public function test_unknown_status_is_rejected()
    {
        $order = $this->order();

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.orders.update', $order->number), [
                'status' => 'выдуманный',
                'phone' => '+7 985 137-92-35',
                'ship_method' => 'courier',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::New, $order->refresh()->status);
    }
}
