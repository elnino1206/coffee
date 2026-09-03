<?php

namespace App\Models;

use App\Enums\Grind;
use App\Enums\Roast;
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
 * @property Roast|null $roast
 * @property bool $subscribe
 * @property int $qty
 */
#[Fillable(['product_variant_id', 'grind', 'roast', 'subscribe', 'qty'])]
class CartItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grind' => Grind::class,
            'roast' => Roast::class,
            'subscribe' => 'boolean',
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
    /**
     * Цена одной штуки с учётом подписки.
     *
     * Скидка считается здесь, а не в итогах корзины: цену позиции
     * показывают и корзина, и окно выбора, и строка заказа — расчёт
     * обязан быть один.
     */
    public function unitPrice(): int
    {
        $price = $this->variant->price;

        if (! $this->subscribe) {
            return $price;
        }

        return (int) round($price * (1 - (float) config('checkout.subscribe_discount')));
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->qty;
    }
}
