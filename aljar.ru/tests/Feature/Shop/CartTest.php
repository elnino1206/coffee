<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\Coffee;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Продолжить работу с той же корзиной.
     *
     * В браузере это делает кука, но тестовый клиент не переносит их
     * между запросами — токен передаётся руками.
     */
    protected function withCart(string $token): static
    {
        return $this->withCookie((string) config('checkout.cart_cookie'), $token);
    }

    protected function cartToken(): string
    {
        return (string) Cart::query()->latest('id')->firstOrFail()->token;
    }

    protected function variant(int $price = 70_000): ProductVariant
    {
        $coffee = Coffee::factory()->create();

        return $coffee->variants()->create(['title' => '250 г', 'price' => $price, 'weight_g' => 250, 'stock' => 10]);
    }

    public function test_guest_can_put_coffee_in_the_cart()
    {
        $variant = $this->variant();

        $this->post(route('cart.store'), [
            'product_variant_id' => $variant->id,
            'grind' => 'espresso',
            'qty' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'grind' => 'espresso',
            'qty' => 2,
        ]);
    }

    public function test_coffee_cannot_be_added_without_a_grind()
    {
        $variant = $this->variant();

        $this->post(route('cart.store'), [
            'product_variant_id' => $variant->id,
            'qty' => 1,
        ])->assertSessionHasErrors('grind');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_equipment_cannot_be_given_a_grind()
    {
        $cezve = Equipment::factory()->create();
        $variant = $cezve->variants()->create(['title' => '500 мл', 'price' => 390_000, 'volume_ml' => 500]);

        $this->post(route('cart.store'), [
            'product_variant_id' => $variant->id,
            'grind' => 'espresso',
            'qty' => 1,
        ])->assertSessionHasErrors('grind');
    }

    public function test_same_variant_and_grind_add_up_instead_of_doubling_the_row()
    {
        $variant = $this->variant();

        $payload = ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1];

        $this->post(route('cart.store'), $payload);
        $this->withCart($this->cartToken())->post(route('cart.store'), $payload);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['qty' => 2]);
    }

    public function test_the_same_coffee_in_two_grinds_is_two_rows()
    {
        $variant = $this->variant();

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1]);
        $this->withCart($this->cartToken())
            ->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'cezve', 'qty' => 1]);

        $this->assertDatabaseCount('cart_items', 2);
        $this->assertSame(1, Cart::query()->count());
    }

    public function test_cart_shows_current_prices_not_the_ones_from_the_moment_of_adding()
    {
        $variant = $this->variant(70_000);

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1]);

        $variant->update(['price' => 90_000]);

        $this->withCart($this->cartToken())
            ->get(route('cart.show'))
            ->assertInertia(fn ($page) => $page->where('goods_total', 90_000));
    }

    public function test_a_stranger_cannot_touch_someone_elses_cart_row()
    {
        $variant = $this->variant();
        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 1]);

        $item = Cart::query()->first()?->items()->first();
        $this->assertNotNull($item);

        // Другой посетитель приходит со своей корзиной: идентификатор
        // чужой строки сам по себе ничего не разрешает.
        $stranger = Cart::query()->create([
            'token' => 'stranger-token',
            'expires_at' => now()->addDay(),
        ]);

        $this->withCart($stranger->token)
            ->patch(route('cart.update', $item), ['qty' => 99])
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'qty' => 1]);
    }

    public function test_guest_cart_moves_to_the_customer_on_login()
    {
        $variant = $this->variant();
        $customer = Customer::factory()->create();

        $this->post(route('cart.store'), ['product_variant_id' => $variant->id, 'grind' => 'whole', 'qty' => 3]);

        $this->withCart($this->cartToken())
            ->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('carts', ['customer_id' => $customer->id]);
        $this->assertSame(3, (int) Cart::query()->where('customer_id', $customer->id)->first()?->items()->sum('qty'));
    }
}
