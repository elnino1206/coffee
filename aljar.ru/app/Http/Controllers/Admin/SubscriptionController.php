<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Подписки в панели управления.
 *
 * Раздел только показывает: паузу и отмену ставит покупатель из
 * кабинета, блокировку — неудачные списания. Кнопка «изменить» здесь
 * означала бы, что менеджер может остановить чужую подписку молча.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
        ]);

        $subscriptions = Subscription::query()
            ->with(['customer', 'variant.product'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $needle = '%'.mb_strtolower($term).'%';

                $query->where(fn (Builder $where) => $where
                    ->whereRaw('lower(number) like ?', [$needle])
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->whereRaw('lower(name) like ?', [$needle])
                        ->orWhereRaw('lower(email) like ?', [$needle])));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Subscriptions', [
            'subscriptions' => $subscriptions->through(fn (Subscription $subscription) => $this->row($subscription)),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statuses' => collect(SubscriptionStatus::cases())->map(fn (SubscriptionStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'number' => $subscription->number,
            'customer' => $subscription->customer->name,
            'email' => $subscription->customer->email,
            // Вариант мог уйти из продажи: подписка остаётся, и менеджеру
            // нужно видеть, кому перестало приезжать.
            'product' => $subscription->variant?->product->name,
            'variant' => $subscription->variant?->title,
            'grind' => $subscription->grind?->label(),
            'frequency' => "каждые {$subscription->frequency_weeks} нед.",
            'next_delivery' => $subscription->nextDeliveryLabel(),
            'charge' => $subscription->chargeTotal(),
            'status' => $subscription->status->value,
            'status_label' => $subscription->status->label(),
            // Плашка красится по смыслу, а не по имени статуса:
            // заблокированная — это сорванная оплата, а не пауза.
            'pill' => $subscription->status->pill(),
        ];
    }
}
