<?php

namespace App\Enums;

/**
 * Тип бизнеса в оптовой заявке. Список из ТЗ 5.4.
 */
enum BusinessType: string
{
    case Cafe = 'cafe';
    case Restaurant = 'restaurant';
    case Hotel = 'hotel';
    case Office = 'office';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cafe => 'Кофейня',
            self::Restaurant => 'Ресторан',
            self::Hotel => 'Отель',
            self::Office => 'Офис',
            self::Other => 'Другое',
        };
    }
}
