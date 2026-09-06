<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Подписчик рассылки.
 *
 * Отдельная сущность от подписки на кофе: та про регулярные доставки,
 * эта — про письма. Названия в коде разведены намеренно.
 *
 * @property int $id
 * @property string $email
 * @property string|null $source
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $unsubscribed_at
 */
#[Fillable(['email', 'source', 'confirmed_at', 'unsubscribed_at'])]
class NewsletterSubscriber extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isSubscribed(): bool
    {
        return $this->unsubscribed_at === null;
    }
}
