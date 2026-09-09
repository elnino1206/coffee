<?php

namespace App\Http\Controllers;

use App\Enums\BrewMethod;
use App\Enums\Roast;
use App\Models\Coffee;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Раздел каталога «Кофе».
 *
 * Фильтрация идёт на сервере: по ТЗ 5.1 фильтры работают одновременно и
 * показывают количество найденного, а считать его честно может только та
 * сторона, которая владеет всем каталогом.
 */
class CoffeeCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $products = $this->query($filters)->get()->map(fn (Coffee $coffee) => $this->card($coffee));

        return Inertia::render('catalog/Coffee', [
            'products' => $products,
            'filters' => $filters,
            'facets' => $this->facets(),
            'total' => $products->count(),
        ]);
    }

    public function show(Coffee $coffee): Response
    {
        abort_if($coffee->is_hidden, 404);

        $coffee->load(['detail', 'variants', 'related.variants']);

        return Inertia::render('catalog/CoffeeProduct', [
            'coffee' => [
                ...$this->card($coffee),
                'full_name' => $coffee->full_name,
                'origin' => $coffee->detail?->origin,
                'region' => $coffee->detail?->region,
                'process' => $coffee->detail?->process,
                'method' => $coffee->detail?->brew_method->label(),
                'profile' => [
                    'fruity' => $coffee->detail?->profile_fruity,
                    'chocolate' => $coffee->detail?->profile_chocolate,
                    'spice' => $coffee->detail?->profile_spice,
                    'body' => $coffee->detail?->profile_body,
                    'acidity' => $coffee->detail?->profile_acidity,
                ],
                'variants' => $coffee->variants
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($variant) => [
                        'id' => $variant->id,
                        'title' => $variant->title,
                        'price' => $variant->price,
                        'weight_g' => $variant->weight_g,
                        'in_stock' => $variant->inStock(),
                    ]),
            ],
            'related' => $coffee->related->map(fn ($product) => [
                'slug' => $product->slug,
                'name' => $product->name,
                'image' => $product->image_path,
                'price_from' => $product->variants->min('price'),
            ]),
        ]);
    }

    /**
     * Разобрать фильтры из адреса.
     *
     * @return array<string, mixed>
     */
    protected function filters(Request $request): array
    {
        /* Ссылки с главной пишутся коротко: `?method=cezve`, а не
           `?method[]=cezve`. Такой адрес можно продиктовать и положить в
           рассылку, поэтому одиночное значение приводим к списку до
           проверки, а не заставляем разметку знать про синтаксис
           массивов. */
        foreach (['roast', 'method', 'origin'] as $key) {
            if ($request->filled($key) && ! is_array($request->input($key))) {
                $request->merge([$key => [$request->input($key)]]);
            }
        }

        $validated = $request->validate([
            'roast' => ['array'],
            'roast.*' => ['string', 'in:'.implode(',', array_column(Roast::cases(), 'value'))],
            'method' => ['array'],
            'method.*' => ['string', 'in:'.implode(',', array_column(BrewMethod::cases(), 'value'))],
            'origin' => ['array'],
            'origin.*' => ['string'],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'in:rating,price-asc,price-desc'],
        ]);

        return [
            'roast' => $validated['roast'] ?? [],
            'method' => $validated['method'] ?? [],
            'origin' => $validated['origin'] ?? [],
            'price_min' => $validated['price_min'] ?? null,
            'price_max' => $validated['price_max'] ?? null,
            'q' => $validated['q'] ?? null,
            // 'fresh' осталось от сортировки по дате обжарки, которую
            // убрали: значения нет ни в валидации, ни в match, а select на
            // витрине не находил такой option и рисовался пустым.
            'sort' => $validated['sort'] ?? 'rating',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Coffee>
     */
    protected function query(array $filters): Builder
    {
        $query = Coffee::query()
            ->visible()
            // Варианты грузятся вместе с товарами: карточка отдаёт их
            // окну быстрого добавления, и без жадной загрузки каталог
            // делал бы отдельный запрос на каждую позицию.
            ->with(['detail', 'variants'])
            // Цена «от» — минимальная среди вариантов: по ней же идёт и
            // сортировка, поэтому считается запросом, а не в PHP.
            ->withMin('variants as price_from', 'price');

        $query->when($filters['roast'], fn (Builder $q, array $roast) => $q->whereHas(
            'detail', fn (Builder $detail) => $detail->whereIn('roast', $roast),
        ));

        $query->when($filters['method'], fn (Builder $q, array $method) => $q->whereHas(
            'detail', fn (Builder $detail) => $detail->whereIn('brew_method', $method),
        ));

        $query->when($filters['origin'], fn (Builder $q, array $origin) => $q->whereHas(
            'detail', fn (Builder $detail) => $detail->whereIn('origin', $origin),
        ));

        // Цена в фильтре — рубли, в базе — копейки.
        $query->when($filters['price_min'], fn (Builder $q, int $min) => $q->whereHas(
            'variants', fn (Builder $v) => $v->where('price', '>=', $min * 100),
        ));

        $query->when($filters['price_max'], fn (Builder $q, int $max) => $q->whereHas(
            'variants', fn (Builder $v) => $v->where('price', '<=', $max * 100),
        ));

        // Регистр приводится с обеих сторон: покупатель ищет «жасмин», а в
        // базе «Жасмин».
        $query->when($filters['q'], function (Builder $q, string $term): void {
            $needle = '%'.mb_strtolower($term).'%';

            $q->where(fn (Builder $where) => $where
                ->whereRaw('lower(products.name) like ?', [$needle])
                ->orWhereHas('detail', fn (Builder $detail) => $detail
                    ->whereRaw('lower(notes) like ?', [$needle])
                    ->orWhereRaw('lower(origin) like ?', [$needle])
                    ->orWhereRaw('lower(region) like ?', [$needle])));
        });

        return match ($filters['sort']) {
            'price-asc' => $query->orderBy('price_from'),
            'price-desc' => $query->orderByDesc('price_from'),
            default => $query->orderByDesc('rating_avg'),
        };
    }

    /**
     * Наборы значений для панели фильтров.
     *
     * @return array<string, mixed>
     */
    protected function facets(): array
    {
        $visible = Coffee::query()->visible();

        return [
            'roasts' => collect(Roast::cases())->map(fn (Roast $roast) => [
                'value' => $roast->value,
                'label' => $roast->label(),
                'count' => (clone $visible)->whereHas('detail', fn (Builder $d) => $d->where('roast', $roast))->count(),
            ]),
            'methods' => collect(BrewMethod::cases())->map(fn (BrewMethod $method) => [
                'value' => $method->value,
                'label' => $method->label(),
                'count' => (clone $visible)->whereHas('detail', fn (Builder $d) => $d->where('brew_method', $method))->count(),
            ]),
            // Агрегат берётся конструктором запросов, а не моделью:
            // count(*) — не свойство характеристик кофе.
            'origins' => DB::table('coffee_details')
                ->select('origin', DB::raw('count(*) as count'))
                ->whereIn('product_id', (clone $visible)->select('products.id')->toBase())
                ->groupBy('origin')
                ->orderBy('origin')
                ->get()
                ->map(fn (object $row) => ['value' => $row->origin, 'label' => $row->origin, 'count' => (int) $row->count]),
            'price' => [
                'min' => (int) floor(((clone $visible)->join('product_variants', 'product_variants.product_id', '=', 'products.id')->min('price') ?? 0) / 100),
                'max' => (int) ceil(((clone $visible)->join('product_variants', 'product_variants.product_id', '=', 'products.id')->max('price') ?? 0) / 100),
            ],
        ];
    }

    /**
     * Подсказки для панели поиска в шапке.
     *
     * Отдельный метод, а не выдача каталога: панели нужны только имя,
     * ссылка и цена, и отдавать ради подсказки весь набор фильтров и
     * фасетов — впустую гонять данные на каждое нажатие клавиши.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if ($term === '') {
            return response()->json(['results' => []]);
        }

        $needle = '%'.mb_strtolower($term).'%';

        $hits = Coffee::query()
            ->visible()
            ->with('variants')
            ->where(fn (Builder $q) => $q
                ->whereRaw('lower(products.name) like ?', [$needle])
                ->orWhereHas('detail', fn (Builder $d) => $d
                    ->whereRaw('lower(notes) like ?', [$needle])
                    ->orWhereRaw('lower(origin) like ?', [$needle])
                    ->orWhereRaw('lower(region) like ?', [$needle])))
            // Подсказка обязана оставаться подсказкой: длинный список
            // перекрывает страницу и перестаёт помогать.
            ->limit(6)
            ->get()
            ->map(fn (Coffee $coffee): array => [
                'slug' => $coffee->slug,
                'name' => $coffee->name,
                'price_from' => (int) ($coffee->variants->min('price') ?? 0),
            ]);

        return response()->json(['results' => $hits]);
    }

    /**
     * Карточка товара для витрины.
     *
     * @return array<string, mixed>
     */
    protected function card(Coffee $coffee): array
    {
        return [
            'slug' => $coffee->slug,
            'name' => $coffee->name,
            'notes' => $coffee->detail?->notes,
            'image' => $coffee->image_path,
            'species' => $coffee->detail?->species,
            // Происхождение и регион — подзаголовок карточки каталога.
            'origin' => $coffee->detail?->origin,
            'region' => $coffee->detail?->region,
            'roast' => $coffee->detail?->roast->label(),
            // Значение, а не подпись: окно выбора предвыбирает по нему.
            'roast_value' => $coffee->detail?->roast->value,
            'rating' => (float) $coffee->rating_avg,
            'reviews' => $coffee->reviews_count,
            'price_from' => (int) ($coffee->price_from ?? $coffee->variants->min('price') ?? 0),
            // Вес, к которому относится цена «от». В прототипе он был
            // зашит как «250 г»; здесь берётся у самого дешёвого варианта,
            // иначе подпись разойдётся с ценой.
            'price_from_title' => $coffee->variants->sortBy('price')->first()?->title,
            // Варианты нужны окну быстрого добавления: без них кнопка на
            // карточке выбирала бы вес за покупателя.
            'variants' => $coffee->variants
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'title' => $variant->title,
                    'price' => $variant->price,
                    'in_stock' => $variant->inStock(),
                ])
                ->values()
                ->all(),
        ];
    }
}
