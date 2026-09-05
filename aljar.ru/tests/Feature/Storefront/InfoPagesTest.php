<?php

namespace Tests\Feature\Storefront;

use App\Models\Coffee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InfoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_page_offers_coffee_from_the_catalog()
    {
        $coffee = Coffee::factory()->create(['name' => 'Pink Bourbon']);
        $coffee->variants()->create(['title' => '1 кг', 'price' => 288_000, 'weight_g' => 1000]);

        $this->get(route('info.subscription'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('info/Subscription')
                ->where('discount', 10)
                ->where('coffee.0.name', 'Pink Bourbon')
                ->where('coffee.0.variants.0.price', 288_000)
                ->has('frequencies', 3)
                ->has('grinds', 4),
            );
    }

    public function test_hidden_coffee_is_not_offered_for_subscription()
    {
        $hidden = Coffee::factory()->hidden()->create();
        $hidden->variants()->create(['title' => '250 г', 'price' => 70_000, 'weight_g' => 250]);

        $this->get(route('info.subscription'))
            ->assertInertia(fn (Assert $page) => $page->has('coffee', 0));
    }

    public function test_coffee_without_variants_is_not_offered_for_subscription()
    {
        // Подписаться не на что: цена берётся с варианта, а его нет.
        Coffee::factory()->create();

        $this->get(route('info.subscription'))
            ->assertInertia(fn (Assert $page) => $page->has('coffee', 0));
    }

    public function test_wholesale_page_opens_for_a_guest()
    {
        $this->get(route('info.wholesale'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('info/Wholesale'));
    }

    public function test_blog_lists_articles()
    {
        $this->get(route('info.blog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('info/Blog')
                ->has('articles', 3)
                ->where('articles.0.slug', 'lebanese-coffee'),
            );
    }

    public function test_article_page_shows_the_text_and_the_rest_of_the_journal()
    {
        $this->get(route('info.article', 'how-to-brew-cezve'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('info/Article')
                ->where('article.title', 'Как заваривать кофе в турке')
                ->has('article.body', 4)
                // Сама статья в подборке «ещё в журнале» не повторяется.
                ->has('more', 2),
            );
    }

    public function test_unknown_article_answers_404()
    {
        $this->get(route('info.article', 'no-such-article'))->assertNotFound();
    }
}
