<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Cart\ResolveCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Models\CartItem;
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

        return Inertia::render('shop/Cart', [
            'items' => $cart?->items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'name' => $item->variant->product->name,
                'slug' => $item->variant->product->slug,
                'image' => $item->variant->product->image_path,
                'variant' => $item->variant->title,
                'grind' => $item->grind?->label(),
                'qty' => $item->qty,
                'unit_price' => $item->variant->price,
                'total' => $item->total(),
            ])->values() ?? collect(),
            'goods_total' => $cart?->goodsTotal() ?? 0,
            'free_delivery_from' => (int) config('checkout.free_delivery_from'),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $cart = ($this->resolveCart)($request);
        $grind = $request->input('grind');

        // Одна и та же позиция в том же помоле складывается, а не
        // дублируется строкой.
        $existing = $cart->items()
            ->where('product_variant_id', $request->integer('product_variant_id'))
            ->where('grind', $grind)
            ->first();

        if ($existing instanceof CartItem) {
            $existing->increment('qty', $request->integer('qty'));
        } else {
            $cart->items()->create([
                'product_variant_id' => $request->integer('product_variant_id'),
                'grind' => $grind,
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
