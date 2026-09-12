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

    /**
     * Скраб первого экрана держится на перемотке, а перемотка — на
     * умении сервера отдавать куски файла. Встроенный сервер PHP этого
     * не умеет и отдаёт файл целиком; браузер тогда считает видео
     * неперематываемым и молча отбрасывает каждое присвоение
     * `currentTime` — сцена стоит на первом кадре.
     */
    public function test_scene_film_is_served_in_ranges()
    {
        $this->get(route('media.film', ['film' => 'scene-1.mp4']), ['Range' => 'bytes=1000-1999'])
            ->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 1000-1999/'.filesize(public_path('video/scene-1.mp4')))
            ->assertHeader('Content-Length', '1000');
    }

    public function test_scene_film_route_serves_nothing_but_films()
    {
        $this->get('/media/../.env')->assertNotFound();
        $this->get(route('media.film', ['film' => 'no-such.mp4']))->assertNotFound();
    }

    public function test_home_shows_no_more_than_one_row()
    {
        Coffee::factory(7)->create(['is_featured' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('featured', 4));
    }
}
