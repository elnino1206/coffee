<?php

namespace Tests\Feature\Storefront;

use App\Models\Coffee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_featured_coffee()
    {
        $featured = Coffee::factory()->create(['name' => 'Pink Bourbon', 'is_featured' => true]);
        $featured->variants()->create(['title' => '250 г', 'price' => 70_000, 'weight_g' => 250]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('featured', 1)
                ->where('featured.0.name', 'Pink Bourbon')
                ->where('featured.0.price_from', 70_000)
            );
    }

    public function test_home_skips_coffee_that_is_not_featured()
    {
        Coffee::factory()->create(['is_featured' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('featured', 0));
    }

    public function test_home_never_shows_hidden_coffee()
    {
        Coffee::factory()->create(['is_featured' => true, 'is_hidden' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('featured', 0));
    }

    public function test_card_carries_variants_for_the_add_to_cart_modal()
    {
        $coffee = Coffee::factory()->create(['is_featured' => true]);
        $coffee->variants()->create(['title' => '250 г', 'price' => 70_000, 'weight_g' => 250]);
        $coffee->variants()->create(['title' => '1 кг', 'price' => 252_000, 'weight_g' => 1000]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('featured.0.variants', 2)
                ->where('featured.0.variants.0.title', '250 г')
                ->where('featured.0.variants.0.price', 70_000)
                ->has('featured.0.variants.0.in_stock')
            );
    }

    public function test_home_shows_no_more_than_one_row()
    {
        Coffee::factory(7)->create(['is_featured' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('featured', 4));
    }
}
