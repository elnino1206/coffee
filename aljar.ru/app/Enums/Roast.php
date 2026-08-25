<?php

namespace App\Enums;

enum Roast: string
{
    case Light = 'light';
    case Medium = 'medium';
    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Светлая обжарка',
            self::Medium => 'Средняя обжарка',
            self::Dark => 'Тёмная обжарка',
        };
    }
}
