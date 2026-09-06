<?php

namespace App\Enums;

/**
 * Состояние оптовой заявки. Те же три ступени, что у заказа, но без
 * отмены: заявка либо ждёт ответа, либо в работе, либо закрыта.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Work = 'work';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Work => 'В работе',
            self::Done => 'Закрыта',
        };
    }
}
