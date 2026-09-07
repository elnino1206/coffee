<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Models\Admin;
use App\Models\Article;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArticlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_is_closed_to_customers()
    {
        $this->actingAs(Customer::factory()->create())
            ->get(route('admin.articles.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_list_shows_drafts_and_publications()
    {
        Article::factory()->create(['title' => 'Опубликованная']);
        Article::factory()->draft()->create(['title' => 'Черновик']);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Articles')
                ->has('articles.data', 2)
                // Черновик сверху: с ним ещё предстоит работа.
                ->where('articles.data.0.title', 'Черновик')
            );
    }

    public function test_article_is_created_with_an_address_from_the_title()
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.articles.store'), [
                'title' => 'Проверка публикации',
                'tag' => 'Обжарка',
                'excerpt' => 'Короткий анонс.',
                'body' => "Первый абзац.\n\nВторой абзац.",
                'status' => ArticleStatus::Published->value,
            ])
            ->assertRedirect();

        $article = Article::query()->firstOrFail();

        $this->assertSame('proverka-publikacii', $article->slug);
        $this->assertSame($admin->id, $article->admin_id);
        // Статус боевой, а дату не указали — публикуем сейчас, иначе
        // статья не попала бы на витрину вовсе.
        $this->assertNotNull($article->published_at);
    }

    public function test_two_articles_with_one_title_get_different_addresses()
    {
        $admin = Admin::factory()->create();

        foreach ([1, 2] as $ignored) {
            $this->actingAs($admin, 'admin')->post(route('admin.articles.store'), [
                'title' => 'Одинаковый заголовок',
                'tag' => 'Обжарка',
                'excerpt' => 'Анонс.',
                'status' => ArticleStatus::Draft->value,
            ]);
        }

        $this->assertSame(2, Article::query()->distinct()->count('slug'));
    }

    /** Адрес не меняется при правке: по нему уже могли поставить ссылку. */
    public function test_editing_keeps_the_address()
    {
        $article = Article::factory()->create(['slug' => 'staryi-adres']);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->patch(route('admin.articles.update', $article), [
                'title' => 'Новый заголовок',
                'tag' => $article->tag,
                'excerpt' => $article->excerpt,
                'status' => $article->status->value,
                'published_at' => $article->published_at?->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $article->refresh();

        $this->assertSame('Новый заголовок', $article->title);
        $this->assertSame('staryi-adres', $article->slug);
    }

    public function test_article_can_be_deleted()
    {
        $article = Article::factory()->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->delete(route('admin.articles.destroy', $article))
            ->assertRedirect();

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_article_without_a_title_is_rejected()
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->post(route('admin.articles.store'), [
                'title' => '',
                'tag' => 'Обжарка',
                'excerpt' => 'Анонс.',
                'status' => ArticleStatus::Draft->value,
            ])
            ->assertSessionHasErrors('title');

        $this->assertSame(0, Article::query()->count());
    }
}
