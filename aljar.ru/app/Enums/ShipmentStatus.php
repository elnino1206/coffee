<?php

namespace App\Enums;

/**
 * Состояние отгрузки у перевозчика.
 *
 * Как и у платежа — свой короткий список: у СДЭК полтора десятка кодов
 * на дорогу между складами, и покупателю из них важны три вещи —
 * уехало, приехало, можно забирать.
 */
enum ShipmentStatus: string
{
    /** Заказ передан перевозчику, посылка ещё у нас. */
    case Created = 'created';

    /** В пути: принята на склад или едет между городами. */
    case InTransit = 'in_transit';

    /** На месте: в пункте выдачи или у курьера. */
    case Ready = 'ready';

    case Delivered = 'delivered';

    /** Не вручена и поехала обратно. */
    case Returned = 'returned';

    /** Не вручена: отказ, некорректный заказ, утрата. */
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Передан перевозчику',
            self::InTransit => 'В пути',
            self::Ready => 'Готов к выдаче',
            self::Delivered => 'Вручён',
            self::Returned => 'Возвращён',
            self::Failed => 'Не вручён',
        };
    }

    /**
     * Код состояния СДЭК в наше состояние.
     *
     * Список неполный намеренно: сюда попали коды, значение которых
     * известно из документации перевозчика. `null` означает «код не
     * разобран» — состояние остаётся прежним, а сам код сохраняется в
     * `payload` отгрузки. Промежуточные коды дороги между городами при
     * этом ничего не меняют: для покупателя это всё «в пути».
     *
     * Список нужно сверить с первыми настоящими уведомлениями от СДЭК:
     * проверить его без доступа к контуру перевозчика было негде.
     */
    public static function fromProvider(string $code): ?self
    {
        return match (strtoupper($code)) {
            'ACCEPTED', 'CREATED' => self::Created,
            'RECEIVED_AT_SHIPMENT_WAREHOUSE',
            'READY_FOR_SHIPMENT_IN_SENDER_CITY',
            'TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY',
            'SENT_TO_TRANSIT_CITY',
            'ACCEPTED_IN_TRANSIT_CITY',
            'ACCEPTED_AT_TRANSIT_WAREHOUSE',
            'SENT_TO_RECIPIENT_CITY',
            'ACCEPTED_IN_RECIPIENT_CITY',
            'ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE' => self::InTransit,
            'ACCEPTED_AT_PICK_UP_POINT', 'TAKEN_BY_COURIER' => self::Ready,
            'DELIVERED' => self::Delivered,
            'RETURNED_TO_SENDER_CITY_WAREHOUSE', 'RETURNED_TO_SHIPMENT_WAREHOUSE' => self::Returned,
            'NOT_DELIVERED', 'INVALID' => self::Failed,
            default => null,
        };
    }
}
