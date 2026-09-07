<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_paid_only_by_a_successful_payment()
    {
        $order = Order::factory()->create();

        $this->assertFalse($order->load('payments')->isPaid());

        Payment::factory()->for($order)->failed()->create();

        $this->assertFalse($order->load('payments')->isPaid());

        Payment::factory()->for($order)->succeeded()->create();

        $this->assertTrue($order->load('payments')->isPaid());
    }

    public function test_order_keeps_every_attempt()
    {
        $order = Order::factory()->create();

        Payment::factory()->for($order)->failed()->create();
        Payment::factory()->for($order)->succeeded()->create();

        $this->assertCount(2, $order->load('payments')->payments);
    }

    /**
     * Уведомление о платеже приходит не один раз, и повторное не должно
     * заводить вторую запись о том же списании.
     */
    public function test_provider_payment_id_is_unique_within_provider()
    {
        Payment::factory()->create([
            'provider' => 'tbank',
            'provider_payment_id' => '3021288',
        ]);

        $this->expectException(QueryException::class);

        Payment::factory()->create([
            'provider' => 'tbank',
            'provider_payment_id' => '3021288',
        ]);
    }

    public function test_payload_survives_a_round_trip()
    {
        $payment = Payment::factory()->create([
            'payload' => ['Status' => 'CONFIRMED', 'ErrorCode' => '0'],
        ]);

        $this->assertSame('CONFIRMED', $payment->fresh()?->payload['Status']);
    }

    /**
     * @return array<string, array{string, PaymentStatus}>
     */
    public static function providerStatuses(): array
    {
        return [
            'создан' => ['NEW', PaymentStatus::New],
            'форма открыта' => ['FORM_SHOWED', PaymentStatus::Pending],
            'холд без подтверждения — ещё не оплата' => ['AUTHORIZED', PaymentStatus::Pending],
            'оплачен' => ['CONFIRMED', PaymentStatus::Succeeded],
            'отказ банка' => ['REJECTED', PaymentStatus::Failed],
            'истёк срок' => ['DEADLINE_EXPIRED', PaymentStatus::Failed],
            'отменён' => ['CANCELED', PaymentStatus::Canceled],
            'возврат части' => ['PARTIAL_REFUNDED', PaymentStatus::Refunded],
        ];
    }

    #[DataProvider('providerStatuses')]
    public function test_provider_status_maps_to_our_own(string $code, PaymentStatus $expected)
    {
        $this->assertSame($expected, PaymentStatus::fromProvider($code));
    }

    /**
     * Незнакомый код не должен молча становиться отказом: под ним может
     * оказаться удачное списание.
     */
    public function test_unknown_provider_status_is_not_guessed()
    {
        $this->assertNull(PaymentStatus::fromProvider('SOMETHING_NEW'));
    }

    public function test_only_unfinished_statuses_are_worth_asking_about()
    {
        $this->assertFalse(PaymentStatus::New->isFinal());
        $this->assertFalse(PaymentStatus::Pending->isFinal());
        $this->assertTrue(PaymentStatus::Succeeded->isFinal());
        $this->assertTrue(PaymentStatus::Failed->isFinal());
    }

    /**
     * Идентификатор регулярного списания лежит на покупателе: карта
     * одна, подписок у неё может быть несколько.
     */
    public function test_card_binding_lives_on_the_customer()
    {
        $customer = Customer::factory()->create();

        $this->assertFalse($customer->hasBoundCard());

        $customer->rebill_id = '1234567890';
        $customer->card_mask = '430000******0777';
        $customer->card_bound_at = now();
        $customer->save();

        $this->assertTrue($customer->fresh()?->hasBoundCard());
    }

    /**
     * По идентификатору списывают деньги — на клиенте ему делать нечего.
     */
    public function test_rebill_id_is_hidden_from_serialization()
    {
        $customer = Customer::factory()->create();
        $customer->rebill_id = '1234567890';
        $customer->save();

        $this->assertArrayNotHasKey('rebill_id', $customer->toArray());
    }

    /**
     * Привязку ставит платёжный контур по ответу банка, а не форма
     * профиля.
     */
    public function test_card_binding_is_not_mass_assignable()
    {
        $customer = Customer::factory()->create();

        $customer->fill(['rebill_id' => '999']);

        $this->assertNull($customer->rebill_id);
    }
}
