<?php

namespace App\Http\Controllers;

use App\Actions\Subscriptions\PlaceSubscription;
use App\Enums\Grind;
use App\Http\Requests\Shop\SubscriptionRequest;
use App\Models\Coffee;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Страница подписки.
 *
 * Конструктор считает цену по базе, а не по списку в разметке: иначе он
 * показывал бы цену, которой в каталоге уже нет.
 *
 * Оформление создаёт саму подписку, но не списывает деньги: платёжный
 * контур с рекуррентными списаниями ещё не подключён. Пока это запись, с
 * которой работают кабинет и панель управления.
 */
class SubscriptionController extends Controller
{
    public function show(Request $request): Response
    {
        $coffee = Coffee::query()
            ->visible()
            ->with(['variants' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->map(fn (Coffee $item) => [
                'slug' => $item->slug,
                'name' => $item->name,
                'variants' => $item->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'title' => $variant->title,
                    'price' => $variant->price,
                ])->values(),
            ])
            ->filter(fn (array $item) => $item['variants']->isNotEmpty())
            ->values();

        return Inertia::render('info/Subscription', [
            'coffee' => $coffee,
            'grinds' => collect(Grind::cases())->map(fn (Grind $grind) => [
                'value' => $grind->value,
                'label' => $grind->label(),
            ]),
            'frequencies' => collect((array) config('subscription.frequencies'))
                ->map(fn (int $weeks) => [
                    'value' => $weeks,
                    'label' => "Каждые {$weeks} недели",
                ]),
            'discount' => (int) config('subscription.default_discount_percent'),
            // Оформить может только вошедший: подписка принадлежит
            // покупателю, и гостю её некуда положить.
            'signedIn' => $request->user() !== null,
        ]);
    }

    public function store(SubscriptionRequest $request, PlaceSubscription $placeSubscription): RedirectResponse
    {
        $data = $request->validated();

        $subscription = $placeSubscription($this->customer($request), [
            'product_variant_id' => (int) $data['product_variant_id'],
            'grind' => isset($data['grind']) ? Grind::from($data['grind']) : null,
            'frequency_weeks' => (int) $data['frequency_weeks'],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Подписка {$subscription->number} оформлена.",
        ]);

        return to_route('account');
    }
}
