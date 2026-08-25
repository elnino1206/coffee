<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Work = 'work';
    case Done = 'done';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Work => 'В работе',
            self::Done => 'Выполнен',
            self::Canceled => 'Отменён',
        };
    }
}
