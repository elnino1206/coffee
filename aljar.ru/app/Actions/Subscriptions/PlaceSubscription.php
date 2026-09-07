<?php

namespace App\Actions\Subscriptions;

use App\Enums\Grind;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\NumberSequence;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Оформить подписку.
 *
 * Скидка переносится сюда из настроек числом и дальше живёт в записи:
 * снижение процента задним числом не должно менять условия тем, кто
 * подписался раньше.
 */
class PlaceSubscription
{
    /**
     * @param  array{product_variant_id: int, grind?: Grind|null, frequency_weeks: int}  $data
     */
    public function __invoke(Customer $customer, array $data): Subscription
    {
        return DB::transaction(fn (): Subscription => Subscription::query()->create([
            'number' => NumberSequence::next('subscription'),
            'customer_id' => $customer->id,
            'product_variant_id' => $data['product_variant_id'],
            'grind' => $data['grind'] ?? null,
            'frequency_weeks' => $data['frequency_weeks'],
            // Цикл считается от оформления. Когда подключат списания,
            // первая отгрузка уйдёт вместе с первым платежом, и эта дата
            // станет датой второй.
            'next_delivery_at' => now()->addWeeks($data['frequency_weeks'])->toDateString(),
            'status' => SubscriptionStatus::Active,
            'discount_percent' => (int) config('subscription.default_discount_percent'),
        ]));
    }
}
