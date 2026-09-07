<?php

namespace App\Enums;

/**
 * Состояние подписки.
 *
 * Пауза и блокировка — разные состояния, и сводить их в одно нельзя.
 * Паузу ставит покупатель и снимает сам одной кнопкой; блокировку ставит
 * система после трёх неудачных списаний, и снимается она только удачной
 * оплатой. Один статус на двоих означал бы либо кнопку снятия блокировки
 * у покупателя, либо запрет вернуться из собственной паузы.
 */
enum SubscriptionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Blocked = 'blocked';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Активна',
            self::Paused => 'На паузе',
            self::Blocked => 'Заблокирована',
            self::Canceled => 'Отменена',
        };
    }

    /**
     * Класс плашки в таблицах. Заблокированная подписка красится как
     * ошибка: это не пауза по желанию, а сорванная оплата.
     */
    public function pill(): string
    {
        return match ($this) {
            self::Active => 'new',
            self::Paused => 'paused',
            self::Blocked => 'canceled',
            self::Canceled => 'canceled',
        };
    }
}
