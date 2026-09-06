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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function order(OrderStatus $status, int $price, ?string $when = null): Order
    {
        $coffee = Coffee::factory()->create();
        $variant = $coffee->variants()->create(['title' => '250 г', 'price' => $price, 'weight_g' => 250]);

        $order = Order::query()->create([
            'number' => 'AJ-'.fake()->unique()->numberBetween(2400, 9999),
            'phone' => '+7 985 137-92-35',
            'contact_name' => 'Мария Полякова',
            'ship_method' => ShipMethod::Pickup,
            'pay_method' => PayMethod::Card,
            'status' => $status,
            'placed_at' => $when ? now()->parse($when) : now(),
        ]);

        $order->lines()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $coffee->name,
            'variant_title' => '250 г',
            'weight_g' => 250,
            'unit_price' => $price,
            'qty' => 1,
        ]);

        return $order;
    }

    public function test_dashboard_is_closed_to_guests_and_to_customers()
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $this->actingAs(Customer::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_counts_orders_and_revenue()
    {
        $this->order(OrderStatus::New, 70_000);
        $this->order(OrderStatus::Done, 30_000);
        // Отменённый в выручку не идёт.
        $this->order(OrderStatus::Canceled, 500_000);
        // Старый заказ — за пределами недели.
        $this->order(OrderStatus::Done, 900_000, '-2 months');

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('stats.0.value', '3')
                ->where('stats.1.value', '1 000 ₽')
                ->where('stats.2.value', '1')
                ->has('orders', 4),
            );
    }

    public function test_menu_shows_how_many_orders_wait()
    {
        $this->order(OrderStatus::New, 70_000);
        $this->order(OrderStatus::New, 70_000);
        $this->order(OrderStatus::Done, 70_000);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('adminCounts.orders', 2));
    }

    public function test_customers_do_not_see_admin_counters()
    {
        $this->actingAs(Customer::factory()->create())
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('adminCounts', []));
    }
}
