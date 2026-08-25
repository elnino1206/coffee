<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Последовательность номеров документов.
 *
 * @property string $name
 * @property int $next_value
 */
#[Fillable(['name', 'next_value'])]
class NumberSequence extends Model
{
    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Выдать следующий номер: «AJ-2417».
     *
     * Счётчик берётся под блокировкой строки — два одновременных заказа
     * не должны получить один номер. В SQLite блокировка не работает, но
     * боевая база (MySQL или PostgreSQL) её поддерживает.
     */
    public static function next(string $name): string
    {
        /** @var array{prefix: string, start: int} $settings */
        $settings = config("checkout.numbers.{$name}");

        return DB::transaction(function () use ($name, $settings): string {
            $sequence = static::query()->lockForUpdate()->find($name)
                ?? static::query()->create(['name' => $name, 'next_value' => $settings['start']]);

            $value = $sequence->next_value;

            $sequence->update(['next_value' => $value + 1]);

            return $settings['prefix'].$value;
        });
    }
}
