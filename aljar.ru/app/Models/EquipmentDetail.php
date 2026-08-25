<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Характеристики оборудования. Состав полей ещё уточняется — он зависит
 * от того, что реально пойдёт в продажу.
 *
 * @property int $product_id
 * @property string|null $brand
 * @property string|null $material
 * @property string|null $country
 * @property int|null $warranty_months
 * @property bool $gas_compatible
 * @property bool $electric_compatible
 * @property bool $induction_compatible
 */
#[Fillable([
    'brand', 'material', 'country', 'warranty_months',
    'gas_compatible', 'electric_compatible', 'induction_compatible',
])]
class EquipmentDetail extends Model
{
    protected $primaryKey = 'product_id';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'warranty_months' => 'integer',
            'gas_compatible' => 'boolean',
            'electric_compatible' => 'boolean',
            'induction_compatible' => 'boolean',
        ];
    }

    /**
     * Get the product these attributes describe.
     *
     * @return BelongsTo<Equipment, $this>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'product_id');
    }
}
