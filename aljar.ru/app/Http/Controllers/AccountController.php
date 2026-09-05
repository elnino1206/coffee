<?php

namespace App\Http\Controllers;

use App\Actions\Cart\ResolveCart;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Личный кабинет покупателя.
 *
 * Разметка взята из прототипа versions/v2-anim/account.html. Данные —
 * настоящие: заказы приходят из базы. Подписка и реферальная программа
 * там ещё нарисованы, но у нас их пока нет — панели показывают это
 * прямо, а не подставляют выдуманные значения.
 */
class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        $customer = $this->customer($request);

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with('lines')
            ->latest('placed_at')
            ->get()
            ->map(fn (Order $order) => [
                'number' => $order->number,
                'placed_at' => $order->placedAtLabel(),
                'status' => $order->status->label(),
                'total' => $order->total(),
                // Состав отдаём массивом, а не коллекцией: вложенная
                // коллекция ссорит вывод типов с самим собой — шаблонный
                // параметр у неё инвариантен.
                'lines' => $order->lines->map(fn (OrderLine $line) => [
                    'name' => $line->product_name,
                    'variant' => $line->variant_title,
                    'grind' => $line->grind?->label(),
                    'qty' => $line->qty,
                ])->all(),
                // Повторить можно, пока хотя бы одна позиция ещё продаётся.
                'repeatable' => $order->lines->contains(fn (OrderLine $line) => $line->product_variant_id !== null),
            ]);

        return Inertia::render('account/Index', [
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'orders' => $orders,
        ]);
    }

    /**
     * Повторить заказ: сложить его состав в корзину.
     *
     * По ТЗ 5.3 повтор идёт в один клик. Кладём в корзину, а не создаём
     * заказ сразу: цены и наличие с прошлого раза могли измениться, и
     * человек должен увидеть это до оплаты.
     */
    public function repeat(Request $request, Order $order, ResolveCart $resolveCart): RedirectResponse
    {
        $customer = $this->customer($request);

        abort_if($order->customer_id !== $customer->id, 404);

        $cart = $resolveCart($request);
        $added = 0;

        foreach ($order->lines as $line) {
            if ($line->product_variant_id === null || $line->variant?->is_active !== true) {
                continue;
            }

            $existing = $cart->items()
                ->where('product_variant_id', $line->product_variant_id)
                ->where('grind', $line->grind)
                ->first();

            if ($existing !== null) {
                $existing->increment('qty', $line->qty);
            } else {
                $cart->items()->create([
                    'product_variant_id' => $line->product_variant_id,
                    'grind' => $line->grind,
                    'qty' => $line->qty,
                ]);
            }

            $added++;
        }

        $cart->keepAlive();

        if ($added === 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Позиции этого заказа больше не продаются.']);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Состав заказа снова в корзине.']);

        return to_route('cart.show');
    }
}
