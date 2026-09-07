<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriptions_are_closed_to_customers()
    {
        $this->actingAs(Customer::factory()->create())
            ->get(route('admin.subscriptions.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_list_shows_subscriptions()
    {
        $subscription = Subscription::factory()
            ->for(Customer::factory()->create(['name' => 'Мария Полякова']))
            ->create(['frequency_weeks' => 2]);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Subscriptions')
                ->where('subscriptions.data.0.number', $subscription->number)
                ->where('subscriptions.data.0.customer', 'Мария Полякова')
                ->where('subscriptions.data.0.frequency', 'каждые 2 нед.')
                ->where('subscriptions.data.0.status_label', 'Активна'),
            );
    }

    /**
     * У остановленной подписки отгрузки нет, и дата в строке читалась бы
     * как обещание её привезти.
     */
    public function test_paused_row_has_no_next_delivery()
    {
        Subscription::factory()->paused()->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.subscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('subscriptions.data.0.next_delivery', null)
                ->where('subscriptions.data.0.status_label', 'На паузе')
                ->where('subscriptions.data.0.pill', 'paused'),
            );
    }

    /**
     * Заблокированная красится как ошибка: это сорванная оплата, а не
     * пауза по желанию.
     */
    public function test_blocked_row_is_painted_as_an_error()
    {
        Subscription::factory()->blocked()->create();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.subscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('subscriptions.data.0.status_label', 'Заблокирована')
                ->where('subscriptions.data.0.pill', 'canceled'),
            );
    }

    public function test_subscriptions_can_be_filtered_by_status_and_found_by_customer()
    {
        Subscription::factory()
            ->for(Customer::factory()->create(['name' => 'Артём Соколов']))
            ->paused()
            ->create();

        $active = Subscription::factory()
            ->for(Customer::factory()->create(['name' => 'Елена Ким']))
            ->create();

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.subscriptions.index', ['status' => 'active']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('subscriptions.data', 1)
                ->where('subscriptions.data.0.number', $active->number),
            );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.subscriptions.index', ['q' => 'соколов']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('subscriptions.data', 1)
                ->where('subscriptions.data.0.customer', 'Артём Соколов'),
            );
    }
}
