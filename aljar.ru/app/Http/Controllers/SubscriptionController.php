<?php

namespace App\Http\Controllers;

use App\Enums\Grind;
use App\Models\Coffee;
use App\Models\ProductVariant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Страница подписки.
 *
 * Пока витринная: рассказывает про модель и считает цену в конструкторе.
 * Оформление появится вместе с самими подписками — они опираются на
 * рекуррентные списания, а платёжный контур ещё не подключён.
 *
 * Сорта и цены берутся из базы, а не из списка в разметке: иначе
 * конструктор показывал бы цену, которой в каталоге уже нет.
 */
class SubscriptionController extends Controller
{
    public function show(): Response
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
            'frequencies' => collect(config('subscription.frequencies'))
                ->map(fn (int $weeks) => [
                    'value' => $weeks,
                    'label' => "Каждые {$weeks} недели",
                ]),
            'discount' => (int) config('subscription.default_discount_percent'),
        ]);
    }
}
