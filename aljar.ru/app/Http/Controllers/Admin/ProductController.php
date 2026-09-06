<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BrewMethod;
use App\Enums\ProductType;
use App\Enums\Roast;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Каталог в админке.
 *
 * Цена и остаток живут на вариантах, поэтому и правятся по вариантам:
 * одного поля «цена» у товара нет — у килограмма и у 250 г они разные.
 *
 * Скрытая позиция не удаляется: у заказов остаются ссылки на неё, а
 * снятая с витрины позиция должна оставаться видимой в админке — иначе
 * про неё забывают и потом ищут, почему товара нет в каталоге.
 */
class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'roast' => ['nullable', Rule::enum(Roast::class)],
            'type' => ['nullable', Rule::enum(ProductType::class)],
        ]);

        $products = Product::query()
            ->with(['category', 'variants', 'coffeeDetail', 'equipmentDetail'])
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['roast'] ?? null, fn (Builder $q, string $roast) => $q->whereHas(
                'coffeeDetail', fn (Builder $d) => $d->where('roast', $roast),
            ))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $needle = '%'.mb_strtolower($term).'%';

                $query->where(fn (Builder $where) => $where
                    ->whereRaw('lower(products.name) like ?', [$needle])
                    ->orWhereHas('coffeeDetail', fn (Builder $d) => $d
                        ->whereRaw('lower(origin) like ?', [$needle])
                        ->orWhereRaw('lower(region) like ?', [$needle])));
            })
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/Products', [
            'products' => $products->through(fn (Product $product) => $this->row($product)),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'roast' => $filters['roast'] ?? '',
                'type' => $filters['type'] ?? '',
            ],
            'roasts' => collect(Roast::cases())->map(fn (Roast $roast) => [
                'value' => $roast->value,
                'label' => $roast->label(),
            ]),
            'methods' => collect(BrewMethod::cases())->map(fn (BrewMethod $method) => [
                'value' => $method->value,
                'label' => $method->label(),
            ]),
            'types' => collect(ProductType::cases())->map(fn (ProductType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->load(['variants', 'coffeeDetail']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_featured' => ['boolean'],
            'is_hidden' => ['boolean'],
            'wholesale_available' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
            'roast' => ['nullable', Rule::enum(Roast::class)],
            'brew_method' => ['nullable', Rule::enum(BrewMethod::class)],
            'variants' => ['array'],
            // Цена в форме — рубли: копейки сотруднику вводить незачем.
            'variants.*.price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'variants.*.is_active' => ['boolean'],
        ]);

        $product->update([
            'name' => $data['name'],
            'is_featured' => $data['is_featured'] ?? false,
            'is_hidden' => $data['is_hidden'] ?? false,
            'wholesale_available' => $data['wholesale_available'] ?? false,
        ]);

        if ($product->type === ProductType::Coffee && $product->coffeeDetail !== null) {
            $product->coffeeDetail->update(array_filter([
                'notes' => $data['notes'] ?? null,
                'roast' => $data['roast'] ?? null,
                'brew_method' => $data['brew_method'] ?? null,
            ], fn ($value) => $value !== null));
        }

        foreach ($data['variants'] ?? [] as $id => $fields) {
            // Правим только свои варианты: идентификатор из формы сам по
            // себе ничего не разрешает.
            $variant = $product->variants->firstWhere('id', (int) $id);

            $variant?->update([
                'price' => $fields['price'] * 100,
                'stock' => $fields['stock'],
                'is_active' => $fields['is_active'] ?? true,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Позиция «{$product->name}» сохранена."]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Product $product): array
    {
        $coffee = $product->coffeeDetail;

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'image' => $product->image_path,
            'type' => $product->type->value,
            'type_label' => $product->type->label(),
            'category' => $product->category?->name,
            'origin' => $coffee === null ? null : trim($coffee->origin.' · '.($coffee->region ?? '')),
            'notes' => $coffee?->notes,
            'roast' => $coffee?->roast->value,
            'roast_label' => $coffee?->roast->label(),
            'brew_method' => $coffee?->brew_method->value,
            'brew_label' => $coffee?->brew_method->label(),
            'is_featured' => $product->is_featured,
            'is_hidden' => $product->is_hidden,
            'wholesale_available' => $product->wholesale_available,
            'rating' => (float) $product->rating_avg,
            'reviews' => $product->reviews_count,
            'stock' => (int) $product->variants->sum('stock'),
            'price_from' => (int) ($product->variants->min('price') ?? 0),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'title' => $variant->title,
                // В форму цена уходит рублями, в базе живёт копейками.
                'price' => intdiv($variant->price, 100),
                'stock' => $variant->stock,
                'is_active' => $variant->is_active,
            ])->all(),
        ];
    }
}
