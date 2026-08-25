<?php

namespace App\Models;

use App\Enums\ProductType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Категория каталога. Живёт внутри своего типа: «Турки» относятся к
 * оборудованию и в разделе кофе не показываются.
 *
 * @property int $id
 * @property ProductType $type
 * @property string $slug
 * @property string $name
 * @property int $sort
 */
#[Fillable(['type', 'slug', 'name', 'sort'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
        ];
    }

    /**
     * Get the products in the category.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
