<?php

namespace Tests\Feature\Account;

use App\Enums\Grind;
use App\Enums\OrderStatus;
use App\Enums\PayMethod;
use App\Enums\ShipMethod;
use App\Models\Cart;
use App\Models\Coffee;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function variant(int $price = 70_000): ProductVariant
    {
        $coffee = Coffee::factory()->create(['name' => 'Sidamo']);

        return $coffee->variants()->create([
            'title' => '250 г', 'price' => $price, 'weight_g' => 250, 'stock' => 10,
        ]);
    }

    protected function orderFor(Customer $customer, ?ProductVariant $variant = null): Order
    {
        $variant ??= $this->variant();

        $order = Order::query()->create([
            'number' => 'AJ-2401',
            'customer_id' => $customer->id,
            'contact_name' => $customer->name,
            'phone' => '+7 985 137-92-35',
            'ship_method' => ShipMethod::Courier,
            'address' => 'Москва, Тверская 1',
            'delivery_price' => 35_000,
            'pay_method' => PayMethod::Card,
            'status' => OrderStatus::Done,
            'placed_at' => now()->subDays(3),
        ]);

        $order->lines()->create([
            'product_variant_id' => $variant->id,
            'product_name' => 'Sidamo',
            'variant_title' => '250 г',
            'weight_g' => 250,
            'unit_price' => $variant->price,
            'grind' => Grind::Filter,
            'qty' => 2,
        ]);

        return $order;
    }

    public function test_guests_are_sent_to_the_login_page()
    {
        $this->get(route('account'))->assertRedirect(route('login'));
    }

    public function test_account_shows_the_customer_and_their_orders()
    {
        $customer = Customer::factory()->create(['name' => 'Мария Полякова']);
        $this->orderFor($customer);

        $this->actingAs($customer)
            ->get(route('account'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/Index')
                ->where('customer.name', 'Мария Полякова')
                ->has('orders', 1)
                ->where('orders.0.number', 'AJ-2401')
                ->where('orders.0.status', 'Выполнен')
                // Дата по-русски: локаль приложения английская, а
                // покупателю нужен «1 сентября», а не «1 September».
                ->where('orders.0.placed_at', now()->subDays(3)->day.' '.[
                    'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
                    'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
                ][now()->subDays(3)->month - 1].' '.now()->subDays(3)->year)
                // 2 × 700 ₽ плюс доставка 350 ₽.
                ->where('orders.0.total', 175_000),
            );
    }

    public function test_account_does_not_show_orders_of_other_customers()
    {
        $customer = Customer::factory()->create();
        $this->orderFor(Customer::factory()->create());

        $this->actingAs($customer)
            ->get(route('account'))
            ->assertInertia(fn (Assert $page) => $page->has('orders', 0));
    }

    public function test_repeat_puts_the_order_back_into_the_cart()
    {
        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)
            ->post(route('account.orders.repeat', $order->number))
            ->assertRedirect(route('cart.show'));

        // Помол сохраняется: он часть выбора, а не подробность заказа.
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $order->lines->first()->product_variant_id,
            'grind' => 'filter',
            'qty' => 2,
        ]);
    }

    public function test_repeating_twice_adds_up_instead_of_doubling_the_row()
    {
        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer);

        $this->actingAs($customer)->post(route('account.orders.repeat', $order->number));
        $this->actingAs($customer)->post(route('account.orders.repeat', $order->number));

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['qty' => 4]);
    }

    public function test_someone_elses_order_cannot_be_repeated()
    {
        $order = $this->orderFor(Customer::factory()->create());

        $this->actingAs(Customer::factory()->create())
            ->post(route('account.orders.repeat', $order->number))
            ->assertNotFound();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_repeat_skips_positions_that_are_no_longer_sold()
    {
        $customer = Customer::factory()->create();
        $variant = $this->variant();
        $order = $this->orderFor($customer, $variant);

        $variant->update(['is_active' => false]);

        $this->actingAs($customer)
            ->post(route('account.orders.repeat', $order->number))
            ->assertRedirect();

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertSame(0, Cart::query()->first()?->items()->count());
    }
}
