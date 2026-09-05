<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Cart\ResolveCart;
use App\Enums\ShipMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Models\CartItem;
use App\Models\Coffee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(protected ResolveCart $resolveCart) {}

    public function show(Request $request): Response
    {
        $cart = ($this->resolveCart)($request, create: false);
        $cart?->load('items.variant.product');

        $goodsTotal = $cart?->goodsTotal() ?? 0;

        return Inertia::render('shop/Cart', [
            'items' => $cart?->items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'name' => $item->variant->product->name,
                'slug' => $item->variant->product->slug,
                'image' => $item->variant->product->image_path,
                'variant' => $item->variant->title,
                'grind' => $item->grind?->label(),
                'roast' => $item->roast?->label(),
                'subscribe' => $item->subscribe,
                'qty' => $item->qty,
                // Цена со скидкой подписки — та же, по которой считается
                // итог. Каталожная здесь разошлась бы с суммой строки.
                'unit_price' => $item->unitPrice(),
                'base_price' => $item->variant->price,
                'total' => $item->total(),
            ])->values() ?? collect(),
            'goods_total' => $goodsTotal,
            // Доставка курьером считается здесь же: в шаблоне итог корзины
            // её уже включает, а не откладывает до оформления.
            'delivery' => ShipMethod::Courier->price($goodsTotal),
            'free_delivery_from' => (int) config('checkout.free_delivery_from'),
            'upsell' => $this->upsell($cart?->items->pluck('variant.product.id')->all() ?? []),
        ]);
    }

    /**
     * «Добавить к заказу»: три позиции, которых в корзине ещё нет.
     *
     * Три — ровно под три колонки на широком экране; сетка выдержит и
     * больше, но блок перестанет читаться как короткая подсказка.
     *
     * @param  array<int, int|string|null>  $exclude
     * @return array<int, array<string, mixed>>
     */
    protected function upsell(array $exclude): array
    {
        return Coffee::query()
            ->visible()
            ->with(['detail', 'variants'])
            ->whereNotIn('products.id', $exclude)
            ->orderByDesc('rating_avg')
            ->limit(3)
            ->get()
            ->map(fn (Coffee $coffee): array => [
                'slug' => $coffee->slug,
                'name' => $coffee->name,
                'notes' => $coffee->detail?->notes,
                'image' => $coffee->image_path,
                'species' => $coffee->detail?->species,
                'roast' => $coffee->detail?->roast->label(),
                'roast_value' => $coffee->detail?->roast->value,
                'price_from' => (int) ($coffee->variants->min('price') ?? 0),
                'variants' => $coffee->variants
                    ->map(fn ($variant): array => [
                        'id' => $variant->id,
                        'title' => $variant->title,
                        'price' => $variant->price,
                        'in_stock' => $variant->inStock(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $cart = ($this->resolveCart)($request);
        $grind = $request->input('grind');
        $roast = $request->input('roast');
        $subscribe = $request->boolean('subscribe');

        // Одна и та же позиция складывается, а не дублируется строкой.
        // В ключ входят все три выбора: разная обжарка или разовая
        // покупка против подписки — это разные позиции и разная цена.
        $existing = $cart->items()
            ->where('product_variant_id', $request->integer('product_variant_id'))
            ->where('grind', $grind)
            ->where('roast', $roast)
            ->where('subscribe', $subscribe)
            ->first();

        if ($existing instanceof CartItem) {
            $existing->increment('qty', $request->integer('qty'));
        } else {
            $cart->items()->create([
                'product_variant_id' => $request->integer('product_variant_id'),
                'grind' => $grind,
                'roast' => $roast,
                'subscribe' => $subscribe,
                'qty' => $request->integer('qty'),
            ]);
        }

        $cart->keepAlive();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Добавили в корзину.']);

        return back();
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($request, $item);

        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $item->update(['qty' => $validated['qty']]);
        $item->cart->keepAlive();

        return back();
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($request, $item);

        $cart = $item->cart;
        $item->delete();
        $cart->keepAlive();

        return back();
    }

    /**
     * Чужую строку править нельзя: корзина гостя держится токеном, и
     * идентификатор строки сам по себе ничего не разрешает.
     */
    protected function authorizeItem(Request $request, CartItem $item): void
    {
        $cart = ($this->resolveCart)($request, create: false);

        abort_if($cart === null || $item->cart_id !== $cart->id, 404);
    }
}
