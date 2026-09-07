<?php

namespace Tests\Feature\Delivery;

use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Невручённый заказ отправляют повторно — это вторая отгрузка того
     * же заказа, а не правка полей первой.
     */
    public function test_order_can_have_more_than_one_shipment()
    {
        $order = Order::factory()->create();

        Shipment::factory()->for($order)->status(ShipmentStatus::Returned)->create();
        Shipment::factory()->for($order)->create();

        $this->assertCount(2, $order->load('shipments')->shipments);
    }

    public function test_provider_status_moves_the_shipment()
    {
        $shipment = Shipment::factory()->create();

        $shipment->applyProviderStatus('DELIVERED');

        $this->assertSame(ShipmentStatus::Delivered, $shipment->fresh()?->status);
    }

    /**
     * Незнакомый код оставляет состояние прежним: сырой ответ всё равно
     * сохраняется, и разобрать случай можно по нему.
     */
    public function test_unknown_provider_status_leaves_the_shipment_alone()
    {
        $shipment = Shipment::factory()->status(ShipmentStatus::InTransit)->create();

        $shipment->applyProviderStatus('SOMETHING_NEW');

        $this->assertSame(ShipmentStatus::InTransit, $shipment->fresh()?->status);
    }

    /**
     * @return array<string, array{string, ShipmentStatus}>
     */
    public static function providerStatuses(): array
    {
        return [
            'создан' => ['ACCEPTED', ShipmentStatus::Created],
            'принят на склад отправителя' => ['RECEIVED_AT_SHIPMENT_WAREHOUSE', ShipmentStatus::InTransit],
            'едет между городами' => ['SENT_TO_TRANSIT_CITY', ShipmentStatus::InTransit],
            'ждёт в пункте выдачи' => ['ACCEPTED_AT_PICK_UP_POINT', ShipmentStatus::Ready],
            'у курьера' => ['TAKEN_BY_COURIER', ShipmentStatus::Ready],
            'вручён' => ['DELIVERED', ShipmentStatus::Delivered],
            'не вручён' => ['NOT_DELIVERED', ShipmentStatus::Failed],
        ];
    }

    #[DataProvider('providerStatuses')]
    public function test_provider_status_maps_to_our_own(string $code, ShipmentStatus $expected)
    {
        $this->assertSame($expected, ShipmentStatus::fromProvider($code));
    }

    /**
     * Выбор пункта выдачи делает покупатель при оформлении, поэтому он
     * лежит на заказе: отгрузки может ещё не быть.
     */
    public function test_order_keeps_the_chosen_pickup_point()
    {
        $order = Order::factory()->create([
            'city_code' => 44,
            'city' => 'Москва',
            'delivery_point_code' => 'MSK1234',
        ]);

        $this->assertSame(44, $order->fresh()?->city_code);
        $this->assertSame('MSK1234', $order->fresh()?->delivery_point_code);
    }

    /**
     * Отгрузка по подписке — обычный заказ со ссылкой на неё: иначе
     * история в кабинете покажет только разовые покупки.
     */
    public function test_order_can_belong_to_a_subscription()
    {
        $subscription = Subscription::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
        ]);

        $this->assertTrue($order->subscription?->is($subscription));
    }

    /**
     * Стоимость перевозки и цена доставки для покупателя — разные
     * величины: бесплатная для покупателя доставка магазину чего-то
     * стоит.
     */
    public function test_carrier_cost_is_separate_from_what_the_customer_paid()
    {
        $order = Order::factory()->create(['delivery_price' => 0]);
        $shipment = Shipment::factory()->for($order)->create(['cost' => 41_000]);

        $this->assertSame(0, $shipment->order->delivery_price);
        $this->assertSame(41_000, $shipment->cost);
    }
}
