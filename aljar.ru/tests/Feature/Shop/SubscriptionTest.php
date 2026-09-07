<?php

namespace Tests\Feature\Shop;

use App\Enums\Grind;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\ProductVariant;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_tells_a_guest_that_signing_in_is_needed()
    {
        ProductVariant::factory()->create();

        $this->get(route('info.subscription'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('info/Subscription')
                ->where('signedIn', false),
            );
    }

    public function test_guest_cannot_place_a_subscription()
    {
        $variant = ProductVariant::factory()->create();

        $this->post(route('info.subscription.store'), [
            'product_variant_id' => $variant->id,
            'frequency_weeks' => 2,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_customer_places_a_subscription()
    {
        $customer = Customer::factory()->create();
        $variant = ProductVariant::factory()->create();

        $this->actingAs($customer)
            ->post(route('info.subscription.store'), [
                'product_variant_id' => $variant->id,
                'grind' => Grind::Espresso->value,
                'frequency_weeks' => 3,
            ])
            ->assertRedirect(route('account'));

        $subscription = Subscription::query()->sole();

        $this->assertSame($customer->id, $subscription->customer_id);
        $this->assertSame($variant->id, $subscription->product_variant_id);
        $this->assertSame(Grind::Espresso, $subscription->grind);
        $this->assertSame(3, $subscription->frequency_weeks);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertStringStartsWith('SUB-', $subscription->number);
        $this->assertSame(
            now()->addWeeks(3)->toDateString(),
            $subscription->next_delivery_at?->toDateString(),
        );
    }

    /**
     * Процент фиксируется в записи: снижение скидки в настройках не
     * должно менять условия тем, кто подписался раньше.
     */
    public function test_discount_is_frozen_at_signup()
    {
        config(['subscription.default_discount_percent' => 15]);

        $customer = Customer::factory()->create();
        $variant = ProductVariant::factory()->create(['price' => 100_000]);

        $this->actingAs($customer)->post(route('info.subscription.store'), [
            'product_variant_id' => $variant->id,
            'frequency_weeks' => 2,
        ])->assertRedirect(route('account'));

        $subscription = Subscription::query()->sole();
        $this->assertSame(15, $subscription->discount_percent);

        config(['subscription.default_discount_percent' => 5]);

        $this->assertSame(15, $subscription->fresh()->discount_percent);
        $this->assertSame(85_000, $subscription->fresh()->chargeTotal());
    }

    public function test_subscription_is_only_for_coffee()
    {
        $customer = Customer::factory()->create();
        $kettle = ProductVariant::factory()->create([
            'product_id' => Equipment::factory(),
        ]);

        $this->actingAs($customer)
            ->post(route('info.subscription.store'), [
                'product_variant_id' => $kettle->id,
                'frequency_weeks' => 2,
            ])
            ->assertSessionHasErrors('product_variant_id');

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_withdrawn_variant_cannot_be_subscribed_to()
    {
        $customer = Customer::factory()->create();
        $variant = ProductVariant::factory()->create(['is_active' => false]);

        $this->actingAs($customer)
            ->post(route('info.subscription.store'), [
                'product_variant_id' => $variant->id,
                'frequency_weeks' => 2,
            ])
            ->assertSessionHasErrors('product_variant_id');
    }

    public function test_frequency_outside_the_settings_is_rejected()
    {
        $customer = Customer::factory()->create();
        $variant = ProductVariant::factory()->create();

        $this->actingAs($customer)
            ->post(route('info.subscription.store'), [
                'product_variant_id' => $variant->id,
                'frequency_weeks' => 7,
            ])
            ->assertSessionHasErrors('frequency_weeks');
    }
}
