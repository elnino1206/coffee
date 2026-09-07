<?php

namespace App\Http\Controllers;

use App\Concerns\FormatsRussianDates;
use App\Models\Article;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Журнал.
 *
 * Статьи приходят из таблицы `articles` и заводятся в админке. Витрина
 * видит только опубликованные и только те, чья дата наступила: статья с
 * завтрашней датой ждёт своего дня.
 */
class BlogController extends Controller
{
    use FormatsRussianDates;

    public function index(): Response
    {
        return Inertia::render('info/Blog', [
            'articles' => $this->published()
                ->map(fn (Article $article) => $this->card($article))
                ->values(),
        ]);
    }

    public function show(string $slug): Response
    {
        $article = Article::query()->published()->where('slug', $slug)->first();

        abort_if($article === null, 404);

        return Inertia::render('info/Article', [
            'article' => [
                ...$this->card($article),
                'body' => $article->paragraphs(),
            ],
            'more' => $this->published()
                ->reject(fn (Article $item) => $item->slug === $slug)
                ->map(fn (Article $item) => $this->card($item))
                ->values(),
        ]);
    }

    /**
     * @return Collection<int, Article>
     */
    protected function published(): Collection
    {
        return Article::query()
            ->published()
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Карточка статьи в списке.
     *
     * @return array<string, mixed>
     */
    protected function card(Article $article): array
    {
        return [
            'slug' => $article->slug,
            'title' => $article->title,
            'tag' => $article->tag,
            'date' => $article->published_at === null
                ? null
                : $this->russianDate($article->published_at),
            'image' => $article->cover_path,
            'excerpt' => $article->excerpt,
        ];
    }
}
