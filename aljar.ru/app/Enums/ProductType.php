<?php

namespace App\Enums;

/**
 * Тип товара. Определяет набор характеристик и правила: подписка и помол
 * существуют только у кофе.
 */
enum ProductType: string
{
    case Coffee = 'coffee';
    case Equipment = 'equipment';

    public function label(): string
    {
        return match ($this) {
            self::Coffee => 'Кофе',
            self::Equipment => 'Для заваривания',
        };
    }

    /**
     * Может ли на товар этого типа быть оформлена подписка.
     */
    public function subscribable(): bool
    {
        return $this === self::Coffee;
    }

    /**
     * Нужно ли выбирать помол при покупке.
     */
    public function requiresGrind(): bool
    {
        return $this === self::Coffee;
    }
}
