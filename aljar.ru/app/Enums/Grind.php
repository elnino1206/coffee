<?php

namespace App\Enums;

/**
 * Помол — свойство строки корзины и заказа, а не вариант товара: он не
 * влияет ни на цену, ни на остаток, но обязан доехать до цеха.
 */
enum Grind: string
{
    case Whole = 'whole';
    case Espresso = 'espresso';
    case Filter = 'filter';
    case Cezve = 'cezve';

    public function label(): string
    {
        return match ($this) {
            self::Whole => 'В зёрнах',
            self::Espresso => 'Под эспрессо',
            self::Filter => 'Под фильтр',
            self::Cezve => 'Под турку',
        };
    }
}
