<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Enums\Roast;
use App\Models\Cart;
use App\Models\Coffee;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function withCart(string $token): static
    {
        return $this->withCookie((string) config('checkout.cart_cookie'), $token);
    }

    protected function cartToken(): string
    {
        return (string) Cart::query()->latest('id')->firstOrFail()->token;
    }

    protected function variant(int $price = 70_000, int $stock = 10): ProductVariant
    {
        $coffee = Coffee::factory()->create(['name' => 'Pink Bourbon']);

        return $coffee->variants()->create([
            'title' => '250 г', 'price' => $price, 'weight_g' => 250, 'stock' => $stock,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function form(array $overrides = []): array
    {
        return [
            'phone' => '+7 985 137-92-35',
            'contact_name' => 'Мария Полякова',
            'ship_method' => 'courier',
            'address' => 'Москва, Тверская 1',
            'pay_method' => 'card',
            'agreement' => '1',
            ...$overrides,
        ];
    }

    public function test_guest_can_place_an_order_without_signing_in()
    {
        $variant = $this->variant();
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'cezve', 'qty' => 2]);

        $this->withCart($this->cartToken())
            ->post(route('checkout.store'), $this->form())
            ->assertRedirect(route('checkout.done'));

        $order = Order::query()->firstOrFail();

        $this->assertNull($order->customer_id);
        $this->assertSame('+7 985 137-92-35', $order->phone);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertStringStartsWith('AJ-', $order->number);
    }

    public function test_order_lines_keep_a_snapshot_of_name_weight_and_price()
    {
        $variant = $this->variant(70_000);
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'espresso', 'qty' => 1]);
        $this->withCart($this->cartToken())->post(route('checkout.store'), $this->form());

        // Прайс изменился уже после покупки — заказ обязан остаться прежним.
        $variant->update(['price' => 99_000]);
        $variant->product?->update(['name' => 'Переименовали']);

        $line = Order::query()->firstOrFail()->lines()->firstOrFail();

        $this->assertSame('Pink Bourbon', $line->product_name);
        $this->assertSame('250 г', $line->variant_title);
        $this->assertSame(250, $line->weight_g);
        $this->assertSame(70_000, $line->unit_price);
    }

    public function test_subscription_choice_reaches_the_order_line_with_its_price()
    {
        $variant = $this->variant(70_000);

        $this->post(route('cart.store'), [
            'product_variant_id' => $variant->id,
            'grind' => 'whole',
            'roast' => 'dark',
            'subscribe' => true,
            'qty' => 2,
        ]);
        $this->withCart($this->cartToken())->post(route('checkout.store'), $this->form());

        $line = Order::query()->firstOrFail()->lines()->firstOrFail();

        $this->assertSame(Roast::Dark, $line->roast);
        $this->assertTrue($line->subscribe);
        // Скидка подписки уже в цене строки: иначе заказ разошёлся бы
        // с тем, что покупатель видел в корзине.
        $this->assertSame(63_000, $line->unit_price);
        $this->assertSame(126_000, $line->total());
    }

    public function test_totals_are_derived_from_the_lines_and_delivery()
    {
        $variant = $this->variant(70_000);
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 2]);
        $this->withCart($this->cartToken())->post(route('checkout.store'), $this->form());

        $order = Order::query()->with('lines')->firstOrFail();

        // 1400 ₽ за товары — до бесплатной доставки не дотягивает.
        $this->assertSame(140_000, $order->goodsTotal());
        $this->assertSame(35_000, $order->delivery_price);
        $this->assertSame(175_000, $order->total());
    }

    public function test_courier_delivery_is_free_above_the_threshold()
    {
        $variant = $this->variant(200_000);
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 2]);
        $this->withCart($this->cartToken())->post(route('checkout.store'), $this->form());

        $this->assertSame(0, Order::query()->firstOrFail()->delivery_price);
    }

    public function test_the_cart_is_emptied_and_stock_goes_down()
    {
        $variant = $this->variant(70_000, stock: 10);
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 3]);
        $this->withCart($this->cartToken())->post(route('checkout.store'), $this->form());

        $this->assertSame(7, $variant->refresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_courier_delivery_demands_an_address()
    {
        $variant = $this->variant();
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1]);

        $this->withCart($this->cartToken())
            ->post(route('checkout.store'), $this->form(['address' => null]))
            ->assertSessionHasErrors('address');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pickup_does_not_demand_an_address()
    {
        $variant = $this->variant();
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1]);

        $this->withCart($this->cartToken())
            ->post(route('checkout.store'), $this->form(['ship_method' => 'pickup', 'address' => null]))
            ->assertRedirect(route('checkout.done'));
    }

    public function test_agreement_is_required()
    {
        $variant = $this->variant();
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1]);

        $this->withCart($this->cartToken())
            ->post(route('checkout.store'), $this->form(['agreement' => null]))
            ->assertSessionHasErrors('agreement');
    }

    public function test_order_of_a_signed_in_customer_is_linked_to_them()
    {
        $customer = Customer::factory()->create();
        $variant = $this->variant();

        $this->actingAs($customer)->post(route('cart.store'), [
            'product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1,
        ]);

        $this->actingAs($customer)->post(route('checkout.store'), $this->form());

        $this->assertSame($customer->id, Order::query()->firstOrFail()->customer_id);
    }

    public function test_empty_cart_sends_you_back_to_the_cart()
    {
        $this->post(route('checkout.store'), $this->form())->assertRedirect(route('cart.show'));
        $this->get(route('checkout.show'))->assertRedirect(route('cart.show'));
    }

    public function test_order_numbers_do_not_repeat()
    {
        $variant = $this->variant(70_000, stock: 50);

        $numbers = collect(range(1, 3))->map(function () use ($variant) {
            $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1]);
            $this->withCart($this->cartToken())->post(route('checkout.store'), $this->form());

            return Order::query()->latest('id')->firstOrFail()->number;
        });

        $this->assertCount(3, $numbers->unique());
    }
}
