<?php

namespace App\Models;

use App\Enums\BrewMethod;
use App\Enums\Roast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Характеристики кофе.
 *
 * @property int $product_id
 * @property string $species
 * @property string $origin
 * @property string|null $region
 * @property string $process
 * @property string $notes
 * @property Roast $roast
 * @property BrewMethod $brew_method
 * @property int $profile_fruity
 * @property int $profile_chocolate
 * @property int $profile_spice
 * @property int $profile_body
 * @property int $profile_acidity
 */
#[Fillable([
    'species', 'origin', 'region', 'process', 'notes', 'roast', 'brew_method',
    'profile_fruity', 'profile_chocolate', 'profile_spice', 'profile_body', 'profile_acidity',
])]
class CoffeeDetail extends Model
{
    protected $primaryKey = 'product_id';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'roast' => Roast::class,
            'brew_method' => BrewMethod::class,
            'profile_fruity' => 'integer',
            'profile_chocolate' => 'integer',
            'profile_spice' => 'integer',
            'profile_body' => 'integer',
            'profile_acidity' => 'integer',
        ];
    }

    /**
     * Get the product these attributes describe.
     *
     * @return BelongsTo<Coffee, $this>
     */
    public function coffee(): BelongsTo
    {
        return $this->belongsTo(Coffee::class, 'product_id');
    }
}
