<?php

namespace Tests\Feature\Blog;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_shows_published_articles_newest_first()
    {
        Article::factory()->create(['title' => 'Старая', 'published_at' => now()->subMonth()]);
        Article::factory()->create(['title' => 'Свежая', 'published_at' => now()->subDay()]);

        $this->get(route('info.blog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('info/Blog')
                ->has('articles', 2)
                ->where('articles.0.title', 'Свежая')
            );
    }

    public function test_draft_is_absent_from_the_journal_and_gives_404()
    {
        $draft = Article::factory()->draft()->create();

        $this->get(route('info.blog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('articles', 0));

        $this->get(route('info.article', $draft->slug))->assertNotFound();
    }

    /** Отложенная публикация ждёт своего дня, а не выходит сразу. */
    public function test_article_dated_in_the_future_is_not_shown_yet()
    {
        $later = Article::factory()->scheduled()->create();

        $this->get(route('info.blog'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('articles', 0));

        $this->get(route('info.article', $later->slug))->assertNotFound();
    }

    public function test_body_is_split_into_paragraphs_by_blank_lines()
    {
        $article = Article::factory()->create([
            'body' => "Первый абзац.\n\nВторой абзац.\n\n\nТретий абзац.",
        ]);

        $this->get(route('info.article', $article->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('info/Article')
                ->has('article.body', 3)
                ->where('article.body.0', 'Первый абзац.')
                ->where('article.body.2', 'Третий абзац.')
            );
    }

    /**
     * Статья без текста остаётся на витрине: страница показывает анонс и
     * пометку, что материал в работе. Так было до переезда в базу.
     */
    public function test_article_without_body_still_opens()
    {
        $article = Article::factory()->create(['body' => null]);

        $this->get(route('info.article', $article->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('article.body', 0));
    }

    public function test_article_date_is_shown_in_russian()
    {
        $article = Article::factory()->create(['published_at' => '2026-08-04 10:00']);

        $this->get(route('info.article', $article->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('article.date', '4 августа 2026'));
    }
}
