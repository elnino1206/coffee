<?php

namespace App\Http\Controllers;

use App\Models\Coffee;
use App\Models\ProductVariant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Главная страница витрины.
 *
 * Раскладка перенесена из прототипа versions/v2-anim/index.html. Из всех
 * блоков динамический один — «Свежая обжарка этой недели»: в прототипе он
 * собирался на клиенте из data.js, здесь приходит с сервера.
 */
class HomeController extends Controller
{
    /**
     * Сколько карточек показываем в подборке.
     *
     * Ряд на широком экране — четыре карточки. Больше — сетка ломается на
     * второй ряд, и блок перестаёт читаться как краткая подборка.
     */
    protected const FEATURED_LIMIT = 4;

    public function index(): Response
    {
        return Inertia::render('Home', [
            'featured' => $this->featured(),
        ]);
    }

    /**
     * Кофе для подборки на главной.
     *
     * @return list<array<string, mixed>>
     */
    protected function featured(): array
    {
        return Coffee::query()
            ->with(['detail', 'variants'])
            ->where('is_featured', true)
            ->where('is_hidden', false)
            ->orderByDesc('rating_avg')
            ->limit(self::FEATURED_LIMIT)
            ->get()
            ->map(fn (Coffee $coffee): array => [
                'slug' => $coffee->slug,
                'name' => $coffee->name,
                'notes' => $coffee->detail?->notes,
                'image' => $coffee->image_path,
                'species' => $coffee->detail?->species,
                'roast' => $coffee->detail?->roast->label(),
                // Значение, а не подпись: окно выбора предвыбирает по нему.
                'roast_value' => $coffee->detail?->roast->value,
                'price_from' => (int) ($coffee->variants->min('price') ?? 0),
                'variants' => $this->variants($coffee),
            ])
            ->all();
    }

    /**
     * Варианты для окна быстрого добавления: без них покупателю нечего
     * выбирать, и кнопка на карточке снова кладёт вес за него.
     *
     * @return list<array<string, mixed>>
     */
    protected function variants(Coffee $coffee): array
    {
        return $coffee->variants
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'title' => $variant->title,
                'price' => $variant->price,
                'in_stock' => $variant->inStock(),
            ])
            ->values()
            ->all();
    }
}
