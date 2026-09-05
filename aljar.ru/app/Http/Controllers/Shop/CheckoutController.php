<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Cart\ResolveCart;
use App\Actions\Orders\PlaceOrder;
use App\Enums\PayMethod;
use App\Enums\ShipMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(protected ResolveCart $resolveCart) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $cart = ($this->resolveCart)($request, create: false);

        if ($cart === null || $cart->load('items')->items->isEmpty()) {
            return to_route('cart.show');
        }

        $cart->load('items.variant.product');
        $goodsTotal = $cart->goodsTotal();

        return Inertia::render('shop/Checkout', [
            // Сводка заказа показывает те же строки, что и корзина:
            // снимок, кадр и все три выбора — вес, обжарка, помол.
            'items' => $cart->items->map(fn (CartItem $item) => [
                'name' => $item->variant->product->name,
                'image' => $item->variant->product->image_path,
                'variant' => $item->variant->title,
                'grind' => $item->grind?->label(),
                'roast' => $item->roast?->label(),
                'subscribe' => $item->subscribe,
                'qty' => $item->qty,
                'total' => $item->total(),
            ]),
            'goods_total' => $goodsTotal,
            'ship_methods' => collect(ShipMethod::cases())->map(fn (ShipMethod $method) => [
                'value' => $method->value,
                'label' => $method->label(),
                'note' => $method->note(),
                'price' => $method->price($goodsTotal),
                'requires_address' => $method->requiresAddress(),
            ]),
            'pay_methods' => collect(PayMethod::cases())->map(fn (PayMethod $method) => [
                'value' => $method->value,
                'label' => $method->label(),
                'note' => $method->note(),
            ]),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $cart = ($this->resolveCart)($request, create: false);

        if ($cart === null || $cart->load('items')->items->isEmpty()) {
            return to_route('cart.show');
        }

        $customer = $request->user();

        $order = $placeOrder($cart, [
            'contact_name' => $request->input('contact_name'),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->input('email'),
            'ship_method' => ShipMethod::from($request->string('ship_method')->toString()),
            'address' => $request->input('address'),
            'pay_method' => PayMethod::from($request->string('pay_method')->toString()),
            'comment' => $request->input('comment'),
        ], $customer instanceof Customer ? $customer : null);

        // Номер кладётся в сессию, а не в адрес: чужой заказ не должен
        // открываться подбором номера.
        return to_route('checkout.done')->with('order', $order->number);
    }

    public function done(Request $request): Response|RedirectResponse
    {
        $number = $request->session()->get('order');

        if (! is_string($number)) {
            return to_route('catalog.coffee.index');
        }

        $order = Order::query()->with('lines')->where('number', $number)->firstOrFail();

        return Inertia::render('shop/Done', [
            'order' => [
                'number' => $order->number,
                'phone' => $order->phone,
                'ship_method' => $order->ship_method->label(),
                'pay_method' => $order->pay_method->label(),
                'delivery_price' => $order->delivery_price,
                'goods_total' => $order->goodsTotal(),
                'total' => $order->total(),
                'lines' => $order->lines->map(fn (OrderLine $line) => [
                    'name' => $line->product_name,
                    'variant' => $line->variant_title,
                    'grind' => $line->grind?->label(),
                    'qty' => $line->qty,
                    'total' => $line->total(),
                ]),
            ],
        ]);
    }
}
