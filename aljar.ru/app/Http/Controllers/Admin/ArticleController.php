<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\FormatsRussianDates;
use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Журнал в админке.
 *
 * Статью можно завести, править и удалить — в отличие от заказов и
 * заявок, это собственный материал магазина, а не слова клиента.
 *
 * Адрес статьи (`slug`) при правке не меняется: по нему уже могли
 * поставить ссылку. Он задаётся один раз при создании, дальше только
 * читается.
 */
class ArticleController extends Controller
{
    use FormatsRussianDates;

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::enum(ArticleStatus::class)],
        ]);

        $articles = Article::query()
            ->with('author')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $needle = '%'.mb_strtolower($term).'%';

                $query->where(fn (Builder $where) => $where
                    ->whereRaw('lower(title) like ?', [$needle])
                    ->orWhereRaw('lower(tag) like ?', [$needle])
                    ->orWhereRaw('lower(excerpt) like ?', [$needle]));
            })
            // Черновики и отложенные — сверху: с ними ещё предстоит работа.
            ->orderByRaw('published_at is null desc')
            ->orderByDesc('published_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Articles', [
            'articles' => $articles->through(fn (Article $article) => $this->row($article)),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statuses' => collect(ArticleStatus::cases())->map(fn (ArticleStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            // Уже использованные рубрики: контентщик переиспользует их, а
            // не изобретает синонимы. Список не запрещает завести новую.
            'tags' => Article::query()->distinct()->orderBy('tag')->pluck('tag'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $article = Article::query()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['title']),
            'admin_id' => $request->user('admin')?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Статья «{$article->title}» заведена."]);

        return back();
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $article->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Статья «{$article->title}» сохранена."]);

        return back();
    }

    public function destroy(Article $article): RedirectResponse
    {
        $title = $article->title;

        $article->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Статья «{$title}» удалена."]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'tag' => ['required', 'string', 'max:40'],
            'excerpt' => ['required', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:40000'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'published_at' => ['nullable', 'date'],
        ]);

        /* Публикация без даты не появится на витрине: выборка требует
           наступившей даты. Раз статус выбран «опубликована», а дату не
           поставили — считаем, что публикуют сейчас. */
        if ($data['status'] === ArticleStatus::Published->value && ($data['published_at'] ?? null) === null) {
            $data['published_at'] = now();
        }

        return $data;
    }

    /**
     * Адрес по заголовку, с хвостом при совпадении.
     *
     * Кириллический заголовок Str::slug превращает в пустую строку —
     * тогда берём дату, иначе адрес был бы просто «-2».
     */
    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'article-'.now()->format('Y-m-d');
        }

        $slug = $base;
        $n = 2;

        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Article $article): array
    {
        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'tag' => $article->tag,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'cover_path' => $article->cover_path,
            'status' => $article->status->value,
            'status_label' => $article->status->label(),
            'published_at' => $article->published_at?->format('Y-m-d\TH:i'),
            'published_label' => $article->published_at === null
                ? null
                : $this->russianDate($article->published_at),
            // Отложенная публикация: статус боевой, а день ещё не настал.
            'is_scheduled' => $article->status === ArticleStatus::Published
                && $article->published_at !== null
                && $article->published_at->isFuture(),
            'has_body' => trim((string) $article->body) !== '',
            'author' => $article->author?->name,
        ];
    }
}
