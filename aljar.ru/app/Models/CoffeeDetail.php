<?php

namespace App\Models;

use App\Enums\BrewMethod;
use App\Enums\Freshness;
use App\Enums\Roast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $roast_date
 * @property int $profile_fruity
 * @property int $profile_chocolate
 * @property int $profile_spice
 * @property int $profile_body
 * @property int $profile_acidity
 */
#[Fillable([
    'species', 'origin', 'region', 'process', 'notes', 'roast', 'brew_method', 'roast_date',
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
            'roast_date' => 'date',
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

    /**
     * Подпись даты обжарки для бейджа: «25 авг».
     *
     * Месяцы заданы списком, а не берутся из локали: в русской локали
     * Carbon это «25 авг.» и «сент.», а на макетах — «авг» и «сен».
     * Витрина должна совпадать с макетом, а не с библиотекой.
     */
    public function roastedAtLabel(): ?string
    {
        if ($this->roast_date === null) {
            return null;
        }

        $months = ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

        return $this->roast_date->day.' '.$months[$this->roast_date->month - 1];
    }

    /**
     * Свежесть считается от даты обжарки и нигде не хранится: хранимое
     * значение протухло бы на следующий день.
     */
    public function freshness(): ?Freshness
    {
        if ($this->roast_date === null) {
            return null;
        }

        return Freshness::fromDays(
            (int) $this->roast_date->startOfDay()->diffInDays(now()->startOfDay()),
        );
    }
}
