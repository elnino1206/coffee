<?php

namespace App\Enums;

/**
 * Способ приготовления, под который обжарен сорт. Фильтр каталога по ТЗ 5.1.
 */
enum BrewMethod: string
{
    case Espresso = 'espresso';
    case Filter = 'filter';
    case Cezve = 'cezve';

    public function label(): string
    {
        return match ($this) {
            self::Espresso => 'Эспрессо',
            self::Filter => 'Фильтр',
            self::Cezve => 'Турка',
        };
    }
}
