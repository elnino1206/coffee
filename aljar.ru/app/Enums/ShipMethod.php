<?php

namespace App\Enums;

enum ShipMethod: string
{
    case Courier = 'courier';
    case Pickup = 'pickup';
    case Post = 'post';

    public function label(): string
    {
        return match ($this) {
            self::Courier => 'Курьер',
            self::Pickup => 'ПВЗ',
            self::Post => 'Почта',
        };
    }

    public function note(): string
    {
        return match ($this) {
            self::Courier => 'По адресу, 1–2 дня',
            self::Pickup => 'Пункт выдачи, 1–3 дня',
            self::Post => '3–7 дней, по тарифу перевозчика',
        };
    }

    /**
     * Нужен ли адрес доставки. У пункта выдачи он свой, у почты — тоже.
     */
    public function requiresAddress(): bool
    {
        return $this === self::Courier;
    }

    /**
     * Стоимость доставки в копейках для заказа на указанную сумму.
     *
     * Тарифы взяты из макета оформления; настоящие появятся вместе со
     * службами доставки, поэтому лежат в конфиге, а не в коде.
     */
    public function price(int $goodsTotal): int
    {
        if ($this !== self::Courier) {
            return 0;
        }

        $freeFrom = (int) config('checkout.free_delivery_from');

        return $goodsTotal >= $freeFrom ? 0 : (int) config('checkout.courier_price');
    }
}
