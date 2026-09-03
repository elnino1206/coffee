<?php

namespace App\Models;

use App\Enums\Grind;
use App\Enums\Roast;
use Database\Factories\OrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property Roast|null $roast
 * @property bool $subscribe
 * @property int $qty
 */
#[Fillable([
    'product_variant_id', 'product_name', 'variant_title', 'weight_g',
    'unit_price', 'grind', 'roast', 'subscribe', 'qty',
])]
class OrderLine extends Model
{
    /** @use HasFactory<OrderLineFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grind' => Grind::class,
            'roast' => Roast::class,
            'subscribe' => 'boolean',
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
