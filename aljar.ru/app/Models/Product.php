<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Общая часть товара: то, что осмысленно и для кофе, и для оборудования.
 * Характеристики каждого типа живут в своей таблице деталей, а работают с
 * ними наследники — Coffee и Equipment.
 *
 * @property int $id
 * @property ProductType $type
 * @property int|null $category_id
 * @property string $slug
 * @property string $name
 * @property string|null $full_name
 * @property string|null $image_path
 * @property bool $is_featured
 * @property bool $is_hidden
 * @property bool $wholesale_available
 * @property string $rating_avg
 * @property int $reviews_count
 * @property string|null $moysklad_id
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'category_id', 'slug', 'name', 'full_name', 'image_path',
    'is_featured', 'is_hidden', 'wholesale_available', 'moysklad_id', 'synced_at',
])]
class Product extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'is_featured' => 'boolean',
            'is_hidden' => 'boolean',
            'wholesale_available' => 'boolean',
            'reviews_count' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Get the category the product belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the coffee-specific attributes, if the product is coffee.
     *
     * Наследники читают деталь через detail(); эта связь нужна там, где
     * список смешанный — в админке одна таблица показывает оба типа.
     *
     * @return HasOne<CoffeeDetail, $this>
     */
    public function coffeeDetail(): HasOne
    {
        return $this->hasOne(CoffeeDetail::class, 'product_id');
    }

    /**
     * Get the equipment-specific attributes, if the product is equipment.
     *
     * @return HasOne<EquipmentDetail, $this>
     */
    public function equipmentDetail(): HasOne
    {
        return $this->hasOne(EquipmentDetail::class, 'product_id');
    }

    /**
     * Get the purchasable variants of the product.
     *
     * Внешний ключ указан явно: у наследников (Coffee, Equipment) связь
     * вывела бы его из имени класса и искала coffee_id.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->orderBy('sort');
    }

    /**
     * Get the products offered alongside this one.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function related(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_related', 'product_id', 'related_product_id')
            ->withPivot('sort')
            ->orderBy('sort');
    }

    /**
     * Limit the query to products shown in the storefront.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_hidden', false);
    }
}
