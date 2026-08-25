<?php

namespace App\Enums;

/**
 * Свежесть обжарки. Не хранится: выводится из даты обжарки, потому что
 * меняется сама по себе — хранимое значение протухнет на следующий день.
 */
enum Freshness: string
{
    case Today = 'today';
    case Peak = 'peak';
    case Fresh = 'fresh';
    case Ageing = 'ageing';

    public static function fromDays(int $days): self
    {
        return match (true) {
            $days <= 0 => self::Today,
            $days <= 3 => self::Peak,
            $days <= 7 => self::Fresh,
            default => self::Ageing,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Обжарено сегодня',
            self::Peak => 'Обжарено 1–3 дня назад',
            self::Fresh => 'Обжарено 4–7 дней назад',
            self::Ageing => 'Обжарено 8+ дней назад',
        };
    }

    public function note(): string
    {
        return match ($this) {
            self::Today => 'Свежайшая обжарка',
            self::Peak => 'Пик вкуса',
            self::Fresh => 'Всё ещё свежий',
            self::Ageing => 'Стоит выпить скорее',
        };
    }
}
