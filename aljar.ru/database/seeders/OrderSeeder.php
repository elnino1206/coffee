<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class OrderSeeder extends Seeder
{
    /**
     * Демонстрационные заказы для админки.
     *
     * Заказы собираются из вариантов, которые уже завёл CatalogSeeder, —
     * иначе в таблице заказов окажутся товары, которых нет в каталоге, и
     * ссылка из строки заказа приведёт в никуда.
     *
     * На боевом окружении сидер не работает: выдуманные заказы попали бы
     * в отчётность и съели бы номера из общей последовательности.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('OrderSeeder пропущен: на production демонстрационные заказы не создаются.');

            return;
        }

        $variants = ProductVariant::query()->with('product')->get();

        if ($variants->isEmpty()) {
            $this->call(CatalogSeeder::class);
            $variants = ProductVariant::query()->with('product')->get();
        }

        $customers = Customer::query()->get();

        if ($customers->count() < 6) {
            $customers = $customers->concat(
                Customer::factory(6 - $customers->count())->create()
            );
        }

        // Разброс по статусам: в админке должна быть видна каждая колонка
        // воронки, а не одна стопка новых заказов. Пары, а не ключи массива:
        // enum ключом быть не может.
        $plan = [
            [OrderStatus::New, 5],
            [OrderStatus::Work, 4],
            [OrderStatus::Done, 8],
            [OrderStatus::Canceled, 2],
        ];

        foreach ($plan as [$status, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $this->order($status, $variants, $customers);
            }
        }
    }

    /**
     * Один заказ с одной-тремя строками.
     *
     * @param  Collection<int, ProductVariant>  $variants
     * @param  Collection<int, Customer>  $customers
     */
    protected function order(OrderStatus $status, Collection $variants, Collection $customers): void
    {
        // Каждый четвёртый — гостевой: оформление без учётной записи
        // предусмотрено, и в выборке админки такие заказы должны быть.
        $customer = fake()->boolean(75) ? $customers->random() : null;

        $order = Order::factory()
            ->status($status)
            ->create([
                'customer_id' => $customer?->id,
                'contact_name' => $customer?->name ?? fake()->name(),
                'email' => $customer?->email,
            ]);

        $picked = $variants->random(min($variants->count(), fake()->numberBetween(1, 3)));

        foreach (Collection::wrap($picked) as $variant) {
            OrderLine::factory()
                ->forVariant($variant)
                ->create([
                    'order_id' => $order->id,
                    'qty' => fake()->numberBetween(1, 3),
                ]);
        }
    }
}
