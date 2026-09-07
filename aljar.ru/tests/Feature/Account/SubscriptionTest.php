<?php

namespace Tests\Feature\Account;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_shows_subscriptions()
    {
        $customer = Customer::factory()->create();
        $subscription = Subscription::factory()->for($customer)->create();

        $this->actingAs($customer)
            ->get(route('account'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/Index')
                ->where('subscriptions.0.number', $subscription->number)
                ->where('subscriptions.0.status', 'active')
                ->where('subscriptions.0.pausable', true)
                ->where('subscriptions.0.resumable', false),
            );
    }

    public function test_pause_stops_the_subscription_and_clears_the_date()
    {
        $customer = Customer::factory()->create();
        $subscription = Subscription::factory()->for($customer)->create();

        $this->actingAs($customer)
            ->post(route('account.subscriptions.pause', $subscription))
            ->assertRedirect();

        $subscription->refresh();

        $this->assertSame(SubscriptionStatus::Paused, $subscription->status);
        $this->assertNotNull($subscription->paused_at);
        // Дата отгрузки снимается: у остановленной подписки её нет, и в
        // интерфейсе она читалась бы как обещание привезти кофе.
        $this->assertNull($subscription->next_delivery_at);
    }

    public function test_resume_counts_the_cycle_from_today()
    {
        $customer = Customer::factory()->create();
        $subscription = Subscription::factory()->for($customer)->paused()->create([
            'frequency_weeks' => 2,
        ]);

        $this->actingAs($customer)
            ->post(route('account.subscriptions.resume', $subscription))
            ->assertRedirect();

        $subscription->refresh();

        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertNull($subscription->paused_at);
        $this->assertSame(
            now()->addWeeks(2)->toDateString(),
            $subscription->next_delivery_at?->toDateString(),
        );
    }

    public function test_cancel_keeps_the_record_and_the_reason()
    {
        $customer = Customer::factory()->create();
        $subscription = Subscription::factory()->for($customer)->create();

        $this->actingAs($customer)
            ->post(route('account.subscriptions.cancel', $subscription), [
                'reason' => 'Уезжаю на полгода.',
            ])
            ->assertRedirect();

        $subscription->refresh();

        $this->assertSame(SubscriptionStatus::Canceled, $subscription->status);
        $this->assertSame('Уезжаю на полгода.', $subscription->cancel_reason);
        $this->assertNotNull($subscription->canceled_at);
        // Запись остаётся: по ТЗ 5.2 у отмены есть история.
        $this->assertDatabaseCount('subscriptions', 1);
    }

    /**
     * Блокировку ставит система после трёх неудачных списаний, и снимает
     * её удачная оплата. Кнопка «возобновить» её снимать не должна.
     */
    public function test_blocked_subscription_cannot_be_resumed_by_the_customer()
    {
        $customer = Customer::factory()->create();
        $subscription = Subscription::factory()->for($customer)->blocked()->create();

        $this->actingAs($customer)
            ->post(route('account.subscriptions.resume', $subscription))
            ->assertRedirect();

        $this->assertSame(SubscriptionStatus::Blocked, $subscription->fresh()->status);
    }

    public function test_blocked_subscription_can_still_be_canceled()
    {
        $customer = Customer::factory()->create();
        $subscription = Subscription::factory()->for($customer)->blocked()->create();

        $this->actingAs($customer)
            ->post(route('account.subscriptions.cancel', $subscription))
            ->assertRedirect();

        $this->assertSame(SubscriptionStatus::Canceled, $subscription->fresh()->status);
    }

    public function test_someone_elses_subscription_does_not_exist()
    {
        $subscription = Subscription::factory()->create();

        $this->actingAs(Customer::factory()->create())
            ->post(route('account.subscriptions.pause', $subscription))
            ->assertNotFound();

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
    }

    public function test_guest_cannot_manage_subscriptions()
    {
        $subscription = Subscription::factory()->create();

        $this->post(route('account.subscriptions.pause', $subscription))
            ->assertRedirect(route('login'));
    }
}
