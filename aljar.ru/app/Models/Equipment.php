<?php

namespace App\Models;

use App\Enums\ProductType;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Оборудование: турки, гейзерные кофеварки, аксессуары. Делит с кофе
 * корзину, заказ и доставку — и ничего больше.
 *
 * @property-read EquipmentDetail|null $detail
 */
class Equipment extends Product
{
    /** @use HasFactory<EquipmentFactory> */
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
        'type' => ProductType::Equipment->value,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(
            'equipment',
            fn (Builder $query) => $query->where('products.type', ProductType::Equipment),
        );
    }

    /**
     * Get the equipment-specific attributes.
     *
     * @return HasOne<EquipmentDetail, $this>
     */
    public function detail(): HasOne
    {
        return $this->hasOne(EquipmentDetail::class, 'product_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Equipment>
     */
    protected static function newFactory(): Factory
    {
        return EquipmentFactory::new();
    }
}
