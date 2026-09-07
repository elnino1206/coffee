<?php

namespace App\Http\Controllers;

use App\Actions\Cart\ResolveCart;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Subscription;
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
            'subscriptions' => $this->subscriptions($customer),
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

    /**
     * Поставить подписку на паузу.
     *
     * Паузу ставит и снимает сам покупатель — этим она и отличается от
     * блокировки, которую снимает только удачная оплата.
     */
    public function pauseSubscription(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ownSubscription($request, $subscription);

        if ($subscription->status !== SubscriptionStatus::Active) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'На паузу можно поставить только активную подписку.']);

            return back();
        }

        $subscription->pause();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Подписка {$subscription->number} на паузе."]);

        return back();
    }

    /**
     * Снять подписку с паузы.
     */
    public function resumeSubscription(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ownSubscription($request, $subscription);

        if ($subscription->status !== SubscriptionStatus::Paused) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Снять с паузы можно только остановленную подписку.']);

            return back();
        }

        $subscription->resume();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Подписка {$subscription->number} снова активна: ближайшая отгрузка {$subscription->nextDeliveryLabel()}.",
        ]);

        return back();
    }

    /**
     * Отменить подписку.
     *
     * Причина не обязательна: требовать объяснение на выходе — способ
     * удержать силой, а не доводом. Запись остаётся у покупателя в
     * истории, оформить заново можно в один клик.
     */
    public function cancelSubscription(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ownSubscription($request, $subscription);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        if (! $subscription->isCancelable()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Подписка уже отменена.']);

            return back();
        }

        $subscription->cancel($data['reason'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Подписка {$subscription->number} отменена."]);

        return back();
    }

    /**
     * Подписки покупателя для кабинета.
     *
     * Отменённые уходят вниз, но остаются на виду: по ним видно прежние
     * условия, и оформить такую же проще, чем вспоминать вес и помол.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function subscriptions(Customer $customer): array
    {
        return Subscription::query()
            ->where('customer_id', $customer->id)
            ->with('variant.product')
            ->orderByRaw('case when status = ? then 1 else 0 end', [SubscriptionStatus::Canceled->value])
            ->latest('id')
            ->get()
            ->map(fn (Subscription $subscription) => [
                'id' => $subscription->id,
                'number' => $subscription->number,
                // Вариант мог уйти из продажи — подписка остаётся, но
                // показывать нечего, кроме прочерка.
                'product' => $subscription->variant?->product->name,
                'variant' => $subscription->variant?->title,
                'grind' => $subscription->grind?->label(),
                'frequency' => "каждые {$subscription->frequency_weeks} нед.",
                'next_delivery' => $subscription->nextDeliveryLabel(),
                'charge' => $subscription->chargeTotal(),
                'discount_percent' => $subscription->discount_percent,
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label(),
                'pill' => $subscription->status->pill(),
                'pausable' => $subscription->status === SubscriptionStatus::Active,
                'resumable' => $subscription->status === SubscriptionStatus::Paused,
                'cancelable' => $subscription->isCancelable(),
            ])
            ->all();
    }

    /**
     * Чужая подписка не существует: 404, а не 403 — по ответу не должно
     * быть видно, что такой номер вообще есть.
     */
    protected function ownSubscription(Request $request, Subscription $subscription): void
    {
        abort_if($subscription->customer_id !== $this->customer($request)->id, 404);
    }
}
