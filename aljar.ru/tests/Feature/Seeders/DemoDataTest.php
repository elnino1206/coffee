<?php

namespace Tests\Feature\Seeders;

use App\Models\Order;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Сидер демо-данных должен отрабатывать на чистой базе.
     *
     * Проверка от конкретной поломки: у каждого четвёртого заказа
     * покупатель намеренно null, и обращение к его имени без проверки
     * роняло `migrate:fresh --seed`, то есть разворачивание проекта
     * с нуля.
     */
    public function test_order_seeder_runs_on_an_empty_database()
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(OrderSeeder::class);

        $this->assertGreaterThan(0, Order::count());
        $this->assertGreaterThan(
            0,
            Order::whereNull('customer_id')->count(),
            'среди демо-заказов должны быть гостевые',
        );
    }

    /** Гостевой заказ всё равно записан на кого-то: имя обязательно. */
    public function test_guest_orders_still_carry_a_contact_name()
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(OrderSeeder::class);

        $nameless = Order::whereNull('contact_name')->orWhere('contact_name', '')->count();

        $this->assertSame(0, $nameless);
    }
}
