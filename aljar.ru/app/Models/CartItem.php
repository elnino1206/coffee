<?php

namespace App\Models;

use App\Enums\Grind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Строка корзины: вариант товара, помол и количество.
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_variant_id
 * @property Grind|null $grind
 * @property int $qty
 */
#[Fillable(['product_variant_id', 'grind', 'qty'])]
class CartItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grind' => Grind::class,
            'qty' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Стоимость строки по текущей цене варианта.
     */
    public function total(): int
    {
        return $this->variant->price * $this->qty;
    }
}
