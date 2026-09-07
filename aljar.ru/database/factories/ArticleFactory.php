<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // sentence() отдаёт строку, в отличие от words(): у того
        // возвращаемый тип — объединение, и его пришлось бы приводить.
        $title = Str::ucfirst(rtrim(fake()->sentence(4), '.'));

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'title' => $title,
            'tag' => fake()->randomElement(['Наследие', 'Приготовление', 'Обжарка']),
            'excerpt' => fake()->sentence(12),
            'body' => implode("\n\n", [fake()->paragraph(), fake()->paragraph(), fake()->paragraph()]),
            'cover_path' => 'img/roaster.webp',
            'status' => ArticleStatus::Published,
            'published_at' => fake()->dateTimeBetween('-3 months'),
            'admin_id' => null,
        ];
    }

    /** Черновик: даты публикации у него нет. */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Draft,
            'published_at' => null,
        ]);
    }

    /** Отложенная публикация: статья готова, но её день ещё не настал. */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published,
            'published_at' => now()->addWeek(),
        ]);
    }
}
