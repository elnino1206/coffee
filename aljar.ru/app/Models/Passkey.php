<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passkeys\Passkey as BasePasskey;

/**
 * Ключ доступа покупателя.
 *
 * Пакет хранит владельца в колонке user_id, но таблица ссылается на
 * customers, и колонка называется customer_id. Разницу закрывает эта
 * модель: связь знает настоящее имя колонки, а user_id остаётся читаемым
 * — внутри пакета есть места, которые сверяют владельца по нему.
 *
 * @property int $customer_id
 */
class Passkey extends BasePasskey
{
    /**
     * Get the customer that owns the passkey.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo($this->ownerModel(), 'customer_id');
    }

    /**
     * Get the model class of the passkey owner.
     *
     * @return class-string<Model>
     */
    protected function ownerModel(): string
    {
        return Customer::class;
    }

    /**
     * Get the owner identifier under the name the package expects.
     *
     * @return Attribute<int, never>
     */
    protected function userId(): Attribute
    {
        return Attribute::get(fn (): int => $this->customer_id);
    }
}
