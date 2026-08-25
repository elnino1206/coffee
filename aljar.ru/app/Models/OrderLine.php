<?php

namespace App\Models;

use App\Enums\Grind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Строка заказа. Название, вес и цена — снимок на момент покупки: без
 * него прошлые заказы поедут при первом изменении прайса.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $product_variant_id
 * @property string $product_name
 * @property string $variant_title
 * @property int|null $weight_g
 * @property int $unit_price
 * @property Grind|null $grind
 * @property int $qty
 */
#[Fillable([
    'product_variant_id', 'product_name', 'variant_title', 'weight_g',
    'unit_price', 'grind', 'qty',
])]
class OrderLine extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grind' => Grind::class,
            'unit_price' => 'integer',
            'weight_g' => 'integer',
            'qty' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function total(): int
    {
        return $this->unit_price * $this->qty;
    }
}
