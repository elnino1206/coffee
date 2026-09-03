<?php

namespace App\Models;

use App\Enums\ProductType;
use Database\Factories\CoffeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Кофе. Отдельная сущность поверх общей таблицы товаров: своя деталь,
 * свои правила, своя карточка. Coffee::query() возвращает только кофе.
 *
 * @property-read CoffeeDetail|null $detail
 */
class Coffee extends Product
{
    /** @use HasFactory<CoffeeFactory> */
    use HasFactory;

    protected $table = 'products';

    /**
     * Тип задан значением по умолчанию, а не обработчиком события: сидеры
     * и Model::withoutEvents отключают события, и товар уходил бы в базу
     * без типа. Значение из базы это не перебивает — при чтении атрибуты
     * заменяются целиком.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'type' => ProductType::Coffee->value,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            'coffee',
            fn (Builder $query) => $query->where('products.type', ProductType::Coffee),
        );
    }

    /**
     * Get the coffee-specific attributes.
     *
     * @return HasOne<CoffeeDetail, $this>
     */
    public function detail(): HasOne
    {
        return $this->hasOne(CoffeeDetail::class, 'product_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Coffee>
     */
    protected static function newFactory(): Factory
    {
        return CoffeeFactory::new();
    }
}
