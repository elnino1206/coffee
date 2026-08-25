<?php

namespace Tests\Feature\Catalog;

use App\Enums\Roast;
use App\Models\Coffee;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoffeeCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_lists_visible_coffee_with_prices()
    {
        $coffee = Coffee::factory()->create(['name' => 'Pink Bourbon']);
        $coffee->variants()->create(['title' => '250 г', 'price' => 70_000, 'weight_g' => 250]);
        $coffee->variants()->create(['title' => '1 кг', 'price' => 252_000, 'weight_g' => 1000]);

        $this->get(route('catalog.coffee.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('catalog/Coffee')
                ->where('total', 1)
                ->where('products.0.name', 'Pink Bourbon')
                // Цена «от» — минимальная среди вариантов, в копейках.
                ->where('products.0.price_from', 70_000),
            );
    }

    public function test_hidden_coffee_never_reaches_the_storefront()
    {
        Coffee::factory()->hidden()->create();

        $this->get(route('catalog.coffee.index'))
            ->assertInertia(fn (Assert $page) => $page->where('total', 0));
    }

    public function test_equipment_does_not_show_up_in_the_coffee_section()
    {
        Equipment::factory()->create();

        $this->get(route('catalog.coffee.index'))
            ->assertInertia(fn (Assert $page) => $page->where('total', 0));
    }

    public function test_filters_narrow_the_results_and_the_count_follows()
    {
        $light = Coffee::factory()->create();
        $light->detail->update(['roast' => Roast::Light]);

        $dark = Coffee::factory()->create();
        $dark->detail->update(['roast' => Roast::Dark]);

        $this->get(route('catalog.coffee.index', ['roast' => ['light']]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('total', 1)
                ->where('products.0.slug', $light->slug),
            );
    }

    public function test_search_looks_inside_tasting_notes()
    {
        $match = Coffee::factory()->create();
        $match->detail->update(['notes' => 'Жасмин, ягоды, мёд']);

        Coffee::factory()->create()->detail->update(['notes' => 'Какао, орех']);

        $this->get(route('catalog.coffee.index', ['q' => 'жасмин']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('total', 1)
                ->where('products.0.slug', $match->slug),
            );
    }

    public function test_products_can_be_sorted_by_price()
    {
        $cheap = Coffee::factory()->create();
        $cheap->variants()->create(['title' => '250 г', 'price' => 50_000, 'weight_g' => 250]);

        $costly = Coffee::factory()->create();
        $costly->variants()->create(['title' => '250 г', 'price' => 150_000, 'weight_g' => 250]);

        $this->get(route('catalog.coffee.index', ['sort' => 'price-asc']))
            ->assertInertia(fn (Assert $page) => $page->where('products.0.slug', $cheap->slug));

        $this->get(route('catalog.coffee.index', ['sort' => 'price-desc']))
            ->assertInertia(fn (Assert $page) => $page->where('products.0.slug', $costly->slug));
    }

    public function test_unknown_filter_values_are_rejected()
    {
        $this->get(route('catalog.coffee.index', ['roast' => ['burnt']]))
            ->assertSessionHasErrors('roast.0');
    }

    public function test_product_page_shows_variants_and_taste_profile()
    {
        $coffee = Coffee::factory()->create(['name' => 'Geisha']);
        $coffee->detail->update(['profile_fruity' => 95]);
        $coffee->variants()->create(['title' => '250 г', 'price' => 110_000, 'weight_g' => 250]);

        $this->get(route('catalog.coffee.show', $coffee->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('catalog/CoffeeProduct')
                ->where('coffee.name', 'Geisha')
                ->where('coffee.profile.fruity', 95)
                ->where('coffee.variants.0.price', 110_000),
            );
    }

    public function test_hidden_product_page_answers_404()
    {
        $coffee = Coffee::factory()->hidden()->create();

        $this->get(route('catalog.coffee.show', $coffee->slug))->assertNotFound();
    }

    public function test_equipment_slug_is_not_reachable_through_the_coffee_route()
    {
        $cezve = Equipment::factory()->create();

        $this->get(route('catalog.coffee.show', $cezve->slug))->assertNotFound();
    }
}
