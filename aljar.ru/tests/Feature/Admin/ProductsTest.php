<?php

namespace Tests\Feature\Admin;

use App\Enums\BrewMethod;
use App\Enums\Roast;
use App\Models\Admin;
use App\Models\Coffee;
use App\Models\Customer;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function coffee(string $name = 'Sidamo'): Coffee
    {
        $coffee = Coffee::factory()->create(['name' => $name]);

        $coffee->variants()->create(['title' => '250 г', 'price' => 70_000, 'weight_g' => 250, 'stock' => 10]);
        $coffee->variants()->create(['title' => '1 кг', 'price' => 252_000, 'weight_g' => 1000, 'stock' => 4]);

        return $coffee;
    }

    public function test_catalog_is_closed_to_customers()
    {
        $this->actingAs(Customer::factory()->create())
            ->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_list_shows_both_types_with_price_and_stock()
    {
        $this->coffee();
        Equipment::factory()->create(['name' => 'Турка медная']);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Products')
                ->has('products.data', 2)
                // Цена «от» — минимальная среди вариантов, остаток — сумма.
                ->where('products.data.0.name', 'Sidamo')
                ->where('products.data.0.price_from', 70_000)
                ->where('products.data.0.stock', 14),
            );
    }

    public function test_hidden_position_stays_in_the_admin_list()
    {
        // Снятая с витрины позиция обязана оставаться видимой сотруднику:
        // иначе про неё забывают и ищут, почему товара нет в каталоге.
        Coffee::factory()->hidden()->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.products.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.is_hidden', true),
            );
    }

    public function test_prices_are_entered_in_roubles_and_stored_in_kopecks()
    {
        $coffee = $this->coffee();
        $variant = $coffee->variants()->first();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->patch(route('admin.products.update', $coffee->id), [
                'name' => 'Sidamo',
                'variants' => [$variant->id => ['price' => 850, 'stock' => 30]],
            ])
            ->assertRedirect();

        $this->assertSame(85_000, $variant->refresh()->price);
        $this->assertSame(30, $variant->stock);
    }

    public function test_a_variant_of_another_product_is_not_touched()
    {
        $coffee = $this->coffee();
        $other = $this->coffee('Cerrado');
        $foreign = $other->variants()->first();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->patch(route('admin.products.update', $coffee->id), [
                'name' => 'Sidamo',
                'variants' => [$foreign->id => ['price' => 1, 'stock' => 0]],
            ]);

        $this->assertSame(70_000, $foreign->refresh()->price);
    }

    public function test_coffee_attributes_and_shelf_flags_are_saved()
    {
        $coffee = $this->coffee();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->patch(route('admin.products.update', $coffee->id), [
                'name' => 'Сидамо',
                'notes' => 'Бергамот, цитрус, цветы',
                'roast' => 'light',
                'brew_method' => 'filter',
                'is_featured' => true,
                'is_hidden' => true,
                'variants' => [],
            ]);

        $coffee->refresh()->load('detail');

        $this->assertSame('Сидамо', $coffee->name);
        $this->assertTrue($coffee->is_featured);
        $this->assertTrue($coffee->is_hidden);
        $this->assertSame(Roast::Light, $coffee->detail?->roast);
        $this->assertSame(BrewMethod::Filter, $coffee->detail?->brew_method);
        $this->assertSame('Бергамот, цитрус, цветы', $coffee->detail?->notes);
    }

    public function test_hidden_product_disappears_from_the_storefront()
    {
        $coffee = $this->coffee();

        $this->get(route('catalog.coffee.index'))
            ->assertInertia(fn (Assert $page) => $page->where('total', 1));

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->patch(route('admin.products.update', $coffee->id), [
                'name' => $coffee->name,
                'is_hidden' => true,
                'variants' => [],
            ]);

        $this->get(route('catalog.coffee.index'))
            ->assertInertia(fn (Assert $page) => $page->where('total', 0));
    }
}
