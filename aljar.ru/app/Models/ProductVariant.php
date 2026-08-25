<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * То, что кладут в корзину: конкретный вес кофе или конкретный объём
 * турки. Цена и остаток живут здесь, а не на товаре.
 *
 * @property int $id
 * @property int $product_id
 * @property string $title
 * @property int $price цена в копейках
 * @property string|null $sku
 * @property int $stock
 * @property int|null $weight_g
 * @property int|null $volume_ml
 * @property bool $is_active
 * @property int $sort
 * @property string|null $moysklad_id
 * @property Carbon|null $synced_at
 */
#[Fillable([
    'title', 'price', 'sku', 'stock', 'weight_g', 'volume_ml',
    'is_active', 'sort', 'moysklad_id', 'synced_at',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'weight_g' => 'integer',
            'volume_ml' => 'integer',
            'is_active' => 'boolean',
            'sort' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Get the product the variant belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Есть ли что отгружать.
     */
    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
