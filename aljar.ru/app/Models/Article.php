<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Статья журнала.
 *
 * Текст хранится сплошным полем, абзацы разделены пустой строкой:
 * редактор пишет текст, а не разметку. Разбор на абзацы — забота
 * витрины, поэтому он живёт здесь, а не в контроллере, и одинаков
 * для списка и для страницы статьи.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $tag
 * @property string $excerpt
 * @property string|null $body
 * @property string|null $cover_path
 * @property ArticleStatus $status
 * @property Carbon|null $published_at
 * @property int|null $admin_id
 */
#[Fillable([
    'slug', 'title', 'tag', 'excerpt', 'body',
    'cover_path', 'status', 'published_at', 'admin_id',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Только то, что видно на витрине.
     *
     * Дата в будущем — это отложенная публикация, а не опечатка:
     * статья со статусом «опубликована» и завтрашней датой ждёт своего
     * дня и до него в журнал не попадает.
     *
     * @param  Builder<Article>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Абзацы текста.
     *
     * @return list<string>
     */
    public function paragraphs(): array
    {
        $body = trim((string) $this->body);

        if ($body === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), preg_split('/\R{2,}/', $body) ?: []),
            fn (string $piece) => $piece !== '',
        ));
    }
}
