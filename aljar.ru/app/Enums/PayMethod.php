<?php

namespace App\Enums;

enum PayMethod: string
{
    case Card = 'card';
    case Sbp = 'sbp';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Банковская карта',
            self::Sbp => 'СБП',
        };
    }

    public function note(): string
    {
        return match ($this) {
            self::Card => 'Visa, Mastercard, Мир',
            self::Sbp => 'Оплата через банк по QR',
        };
    }
}
