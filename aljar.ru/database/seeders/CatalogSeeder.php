<?php

namespace Database\Seeders;

use App\Enums\BrewMethod;
use App\Enums\ProductType;
use App\Enums\Roast;
use App\Models\Category;
use App\Models\Coffee;
use App\Models\CoffeeDetail;
use App\Models\Equipment;
use App\Models\EquipmentDetail;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Каталог для разработки.
 *
 * Кофе перенесён из витринного прототипа (versions/v2-anim/js/data.js) —
 * это настоящие позиции с настоящими ценами и вкусовыми профилями, и на
 * них же рисовались макеты.
 *
 * Цены весов посчитаны по множителям прототипа (1 / 1.9 / 3.6) и
 * округлены до рубля: там, где раньше цена вычислялась на лету, теперь
 * она хранится у варианта, и половина копейки хранению не подлежит.
 *
 * Даты обжарки задаются относительно сегодняшнего дня, а не строками:
 * иначе через неделю весь каталог покажет «обжарено 8+ дней назад».
 *
 * Оборудование — заглушки: реального ассортимента турок и кофеварок ещё
 * нет, а раздел витрины на чём-то показывать надо.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $coffeeCategory = Category::query()->create([
            'type' => ProductType::Coffee,
            'slug' => 'coffee',
            'name' => 'Кофе',
            'sort' => 0,
        ]);

        $cezveCategory = Category::query()->create([
            'type' => ProductType::Equipment,
            'slug' => 'cezve',
            'name' => 'Турки',
            'sort' => 0,
        ]);

        $moka = Category::query()->create([
            'type' => ProductType::Equipment,
            'slug' => 'moka',
            'name' => 'Гейзерные кофеварки',
            'sort' => 1,
        ]);

        foreach ($this->coffee() as $index => $row) {
            // Тип не передаётся: его проставляет сама модель Coffee, и
            // назначить кофе тип оборудования нельзя даже по ошибке.
            $coffee = Coffee::query()->create([
                'category_id' => $coffeeCategory->id,
                'slug' => $row['slug'],
                'name' => $row['name'],
                'full_name' => $row['full_name'],
                'image_path' => $row['image_path'],
                'is_featured' => $row['is_featured'],
            ]);

            $coffee->forceFill([
                'rating_avg' => $row['rating_avg'],
                'reviews_count' => $row['reviews_count'],
            ])->save();

            CoffeeDetail::query()->create([
                'product_id' => $coffee->id,
                ...$row['detail'],
                // Свежие партии сверху списка, залежавшиеся — ниже.
                'roast_date' => now()->subDays($index % 12),
            ]);

            $this->variants($coffee, $row['variants']);
        }

        foreach ($this->equipment($cezveCategory->id, $moka->id) as $row) {
            $equipment = Equipment::query()->create([
                'category_id' => $row['category_id'],
                'slug' => $row['slug'],
                'name' => $row['name'],
            ]);

            EquipmentDetail::query()->create([
                'product_id' => $equipment->id,
                ...$row['detail'],
            ]);

            $this->variants($equipment, $row['variants']);
        }
    }

    /**
     * @param  list<array{0: string, 1: int, 2: int|null, 3?: int|null}>  $variants
     */
    protected function variants(Product $product, array $variants): void
    {
        foreach ($variants as $sort => $variant) {
            [$title, $price, $weight] = $variant;

            $product->variants()->create([
                'title' => $title,
                'price' => $price,
                'weight_g' => $weight,
                'volume_ml' => $variant[3] ?? null,
                'sku' => mb_strtoupper(mb_substr($product->slug, 0, 6)).'-'.($weight ?? $variant[3] ?? 0),
                'stock' => 25 - $sort * 5,
                'sort' => $sort,
            ]);
        }
    }

    /**
     * Сорта из прототипа витрины.
     *
     * @return list<array<string, mixed>>
     */
    protected function coffee(): array
    {
        return [
            [
                'slug' => 'pink-bourbon-mojiana',
                'name' => 'Pink Bourbon & Mojiana',
                'full_name' => 'Арабика 100% Pink Bourbon & Mojiana NY2 FC 16/18',
                'image_path' => 'img/bag-kraft.webp',
                'is_featured' => true,
                'rating_avg' => 4.8,
                'reviews_count' => 18,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Бразилия',
                    'region' => 'Можиана',
                    'process' => 'Натуральный / мытый',
                    'notes' => 'Шоколад, цитрус, карамель',
                    'roast' => Roast::Medium,
                    'brew_method' => BrewMethod::Espresso,
                    'profile_fruity' => 45,
                    'profile_chocolate' => 75,
                    'profile_spice' => 30,
                    'profile_body' => 80,
                    'profile_acidity' => 55,
                ],
                'variants' => [
                    ['250 г', 70000, 250],
                    ['500 г', 133000, 500],
                    ['1 кг', 252000, 1000],
                ],
            ],
            [
                'slug' => 'pink-bourbon',
                'name' => 'Pink Bourbon',
                'full_name' => 'Арабика 100% Pink Bourbon',
                'image_path' => 'img/bag-petrol.webp',
                'is_featured' => true,
                'rating_avg' => 4.9,
                'reviews_count' => 31,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Колумбия',
                    'region' => 'Уила',
                    'process' => 'Мытый',
                    'notes' => 'Жасмин, ягоды, мёд',
                    'roast' => Roast::Light,
                    'brew_method' => BrewMethod::Filter,
                    'profile_fruity' => 85,
                    'profile_chocolate' => 25,
                    'profile_spice' => 20,
                    'profile_body' => 55,
                    'profile_acidity' => 75,
                ],
                'variants' => [
                    ['250 г', 105000, 250],
                    ['500 г', 199500, 500],
                    ['1 кг', 378000, 1000],
                ],
            ],
            [
                'slug' => 'cattura',
                'name' => 'Cattura',
                'full_name' => 'Арабика 100% Cattura',
                'image_path' => 'img/bag-cream.webp',
                'is_featured' => true,
                'rating_avg' => 4.7,
                'reviews_count' => 12,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Бразилия',
                    'region' => 'Серрадо',
                    'process' => 'Натуральный',
                    'notes' => 'Какао, орех, карамель',
                    'roast' => Roast::Medium,
                    'brew_method' => BrewMethod::Espresso,
                    'profile_fruity' => 25,
                    'profile_chocolate' => 85,
                    'profile_spice' => 35,
                    'profile_body' => 95,
                    'profile_acidity' => 35,
                ],
                'variants' => [
                    ['250 г', 80000, 250],
                    ['500 г', 152000, 500],
                    ['1 кг', 288000, 1000],
                ],
            ],
            [
                'slug' => 'cerrado',
                'name' => 'Cerrado',
                'full_name' => 'Арабика 100% Cerrado NY2 FC 16/18',
                'image_path' => 'img/bag-kraft.webp',
                'is_featured' => true,
                'rating_avg' => 4.6,
                'reviews_count' => 22,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Бразилия',
                    'region' => 'Серрадо',
                    'process' => 'Натуральный',
                    'notes' => 'Молочный шоколад, орех',
                    'roast' => Roast::Medium,
                    'brew_method' => BrewMethod::Espresso,
                    'profile_fruity' => 25,
                    'profile_chocolate' => 90,
                    'profile_spice' => 30,
                    'profile_body' => 80,
                    'profile_acidity' => 35,
                ],
                'variants' => [
                    ['250 г', 60000, 250],
                    ['500 г', 114000, 500],
                    ['1 кг', 216000, 1000],
                ],
            ],
            [
                'slug' => 'geisha',
                'name' => 'Geisha',
                'full_name' => 'Арабика 100% Geisha',
                'image_path' => 'img/bag-petrol.webp',
                'is_featured' => false,
                'rating_avg' => 5,
                'reviews_count' => 9,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Эфиопия',
                    'region' => 'Сидамо',
                    'process' => 'Мытый',
                    'notes' => 'Жасмин, бергамот, персик',
                    'roast' => Roast::Light,
                    'brew_method' => BrewMethod::Filter,
                    'profile_fruity' => 95,
                    'profile_chocolate' => 15,
                    'profile_spice' => 20,
                    'profile_body' => 40,
                    'profile_acidity' => 90,
                ],
                'variants' => [
                    ['250 г', 110000, 250],
                    ['500 г', 209000, 500],
                    ['1 кг', 396000, 1000],
                ],
            ],
            [
                'slug' => 'mojiana',
                'name' => 'Mojiana',
                'full_name' => 'Арабика 100% Mojiana NY2 FC 16/18',
                'image_path' => 'img/bag-cream.webp',
                'is_featured' => false,
                'rating_avg' => 4.5,
                'reviews_count' => 14,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Бразилия',
                    'region' => 'Можиана',
                    'process' => 'Натуральный',
                    'notes' => 'Шоколад, сухофрукты',
                    'roast' => Roast::Medium,
                    'brew_method' => BrewMethod::Espresso,
                    'profile_fruity' => 50,
                    'profile_chocolate' => 80,
                    'profile_spice' => 30,
                    'profile_body' => 75,
                    'profile_acidity' => 55,
                ],
                'variants' => [
                    ['250 г', 62000, 250],
                    ['500 г', 117800, 500],
                    ['1 кг', 223200, 1000],
                ],
            ],
            [
                'slug' => 'sul-de-minas',
                'name' => 'Sul De Minas',
                'full_name' => 'Арабика 100% Sul De Minas NY2 FC 16/18',
                'image_path' => 'img/bag-kraft.webp',
                'is_featured' => false,
                'rating_avg' => 4.4,
                'reviews_count' => 11,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Бразилия',
                    'region' => 'Сул-ди-Минас',
                    'process' => 'Натуральный',
                    'notes' => 'Карамель, грецкий орех',
                    'roast' => Roast::Medium,
                    'brew_method' => BrewMethod::Espresso,
                    'profile_fruity' => 25,
                    'profile_chocolate' => 70,
                    'profile_spice' => 35,
                    'profile_body' => 78,
                    'profile_acidity' => 35,
                ],
                'variants' => [
                    ['250 г', 57500, 250],
                    ['500 г', 109300, 500],
                    ['1 кг', 207000, 1000],
                ],
            ],
            [
                'slug' => 'sidamo',
                'name' => 'Sidamo',
                'full_name' => 'Арабика 100% Sidamo',
                'image_path' => 'img/bag-sage.webp',
                'is_featured' => false,
                'rating_avg' => 4.7,
                'reviews_count' => 16,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Эфиопия',
                    'region' => 'Сидамо',
                    'process' => 'Мытый',
                    'notes' => 'Бергамот, цитрус, цветы',
                    'roast' => Roast::Light,
                    'brew_method' => BrewMethod::Filter,
                    'profile_fruity' => 80,
                    'profile_chocolate' => 20,
                    'profile_spice' => 25,
                    'profile_body' => 55,
                    'profile_acidity' => 78,
                ],
                'variants' => [
                    ['250 г', 52500, 250],
                    ['500 г', 99800, 500],
                    ['1 кг', 189000, 1000],
                ],
            ],
            [
                'slug' => 'intensive',
                'name' => 'Intensive',
                'full_name' => 'Смесь Intensive',
                'image_path' => 'img/bag-petrol.webp',
                'is_featured' => false,
                'rating_avg' => 4.6,
                'reviews_count' => 27,
                'detail' => [
                    'species' => 'Смесь арабики',
                    'origin' => 'Смесь',
                    'region' => 'Бразилия / Эфиопия',
                    'process' => 'Смешанный',
                    'notes' => 'Тёмный шоколад, специи',
                    'roast' => Roast::Dark,
                    'brew_method' => BrewMethod::Cezve,
                    'profile_fruity' => 15,
                    'profile_chocolate' => 88,
                    'profile_spice' => 75,
                    'profile_body' => 95,
                    'profile_acidity' => 30,
                ],
                'variants' => [
                    ['250 г', 52500, 250],
                    ['500 г', 99800, 500],
                    ['1 кг', 189000, 1000],
                ],
            ],
            [
                'slug' => 'ground-arabica',
                'name' => 'Молотый арабика',
                'full_name' => 'Кофе молотый арабика Al Jar Coffee',
                'image_path' => 'img/bag-kraft.webp',
                'is_featured' => false,
                'rating_avg' => 4.5,
                'reviews_count' => 20,
                'detail' => [
                    'species' => '100% арабика',
                    'origin' => 'Смесь',
                    'region' => 'Ближний Восток / Бразилия',
                    'process' => 'Смешанный',
                    'notes' => 'Классика, шоколад, орех',
                    'roast' => Roast::Medium,
                    'brew_method' => BrewMethod::Cezve,
                    'profile_fruity' => 35,
                    'profile_chocolate' => 72,
                    'profile_spice' => 40,
                    'profile_body' => 78,
                    'profile_acidity' => 50,
                ],
                'variants' => [
                    ['250 г', 55000, 250],
                    ['500 г', 104500, 500],
                    ['1 кг', 198000, 1000],
                ],
            ],
        ];
    }

    /**
     * Оборудование — временные позиции до появления реального ассортимента.
     *
     * @return list<array<string, mixed>>
     */
    protected function equipment(int $cezveCategory, int $mokaCategory): array
    {
        return [
            [
                'category_id' => $cezveCategory,
                'slug' => 'cezve-copper',
                'name' => 'Турка медная',
                'detail' => [
                    'brand' => 'Al Jar',
                    'material' => 'Медь, латунная ручка',
                    'country' => 'Турция',
                    'warranty_months' => 12,
                    'gas_compatible' => true,
                    'electric_compatible' => true,
                    'induction_compatible' => false,
                ],
                'variants' => [
                    ['300 мл, медь', 320000, null, 300],
                    ['500 мл, медь', 390000, null, 500],
                ],
            ],
            [
                'category_id' => $cezveCategory,
                'slug' => 'cezve-steel',
                'name' => 'Турка стальная',
                'detail' => [
                    'brand' => 'Al Jar',
                    'material' => 'Нержавеющая сталь',
                    'country' => 'Россия',
                    'warranty_months' => 24,
                    'gas_compatible' => true,
                    'electric_compatible' => true,
                    'induction_compatible' => true,
                ],
                'variants' => [
                    ['400 мл, сталь', 270000, null, 400],
                ],
            ],
            [
                'category_id' => $mokaCategory,
                'slug' => 'moka-pot',
                'name' => 'Гейзерная кофеварка',
                'detail' => [
                    'brand' => 'Al Jar',
                    'material' => 'Алюминий',
                    'country' => 'Италия',
                    'warranty_months' => 12,
                    'gas_compatible' => true,
                    'electric_compatible' => true,
                    'induction_compatible' => false,
                ],
                'variants' => [
                    ['на 3 чашки', 350000, null, 150],
                    ['на 6 чашек', 430000, null, 300],
                ],
            ],
        ];
    }
}
