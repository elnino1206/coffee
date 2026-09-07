<?php

namespace Database\Seeders;

use App\Enums\Grind;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\NumberSequence;
use App\Models\ProductVariant;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Демонстрационные подписки для админки и кабинета.
     *
     * Состояния взяты из макета versions/v2-anim/admin/subscriptions.html:
     * раздел должен показывать и активную, и остановленную, и отменённую,
     * иначе колонка статуса ничего не проверяет. Заблокированная добавлена
     * сверх макета — без неё не видно, как выглядит сорванная оплата.
     *
     * На боевом окружении сидер не работает: выдуманные подписки попали
     * бы в отгрузки и съели бы номера из последовательности.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('SubscriptionSeeder пропущен: на production демонстрационные подписки не создаются.');

            return;
        }

        $variants = ProductVariant::query()->whereHas('product', fn ($query) => $query->where('type', 'coffee'))->get();

        if ($variants->isEmpty()) {
            $this->call(CatalogSeeder::class);
            $variants = ProductVariant::query()->whereHas('product', fn ($query) => $query->where('type', 'coffee'))->get();
        }

        $customers = Customer::query()->get();

        if ($customers->count() < 5) {
            $customers = $customers->concat(Customer::factory(5 - $customers->count())->create());
        }

        $plan = [
            ['status' => SubscriptionStatus::Active, 'weeks' => 2, 'grind' => Grind::Espresso],
            ['status' => SubscriptionStatus::Active, 'weeks' => 3, 'grind' => Grind::Whole],
            ['status' => SubscriptionStatus::Paused, 'weeks' => 4, 'grind' => Grind::Filter],
            ['status' => SubscriptionStatus::Blocked, 'weeks' => 2, 'grind' => Grind::Espresso],
            ['status' => SubscriptionStatus::Canceled, 'weeks' => 3, 'grind' => Grind::Cezve],
        ];

        foreach ($plan as $index => $item) {
            $status = $item['status'];

            Subscription::query()->create([
                'number' => NumberSequence::next('subscription'),
                'customer_id' => $customers[$index % $customers->count()]->id,
                'product_variant_id' => $variants[$index % $variants->count()]->id,
                'grind' => $item['grind'],
                'frequency_weeks' => $item['weeks'],
                // Дата есть только у активной: у остальных отгрузки нет.
                'next_delivery_at' => $status === SubscriptionStatus::Active
                    ? now()->addDays(3 + $index)->toDateString()
                    : null,
                'status' => $status,
                'discount_percent' => (int) config('subscription.default_discount_percent'),
                'paused_at' => $status === SubscriptionStatus::Paused ? now()->subDays(5) : null,
                'blocked_at' => $status === SubscriptionStatus::Blocked ? now()->subDays(2) : null,
                'canceled_at' => $status === SubscriptionStatus::Canceled ? now()->subDays(9) : null,
                'cancel_reason' => $status === SubscriptionStatus::Canceled ? 'Уезжаю на полгода.' : null,
                'failed_charge_count' => $status === SubscriptionStatus::Blocked ? 3 : 0,
            ]);
        }
    }
}
