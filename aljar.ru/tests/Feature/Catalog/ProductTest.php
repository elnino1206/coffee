<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductType;
use App\Models\Coffee;
use App\Models\Equipment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_coffee_queries_never_return_equipment()
    {
        Coffee::factory()->count(2)->create();
        Equipment::factory()->create();

        $this->assertSame(3, Product::query()->count());
        $this->assertSame(2, Coffee::query()->count());
        $this->assertSame(1, Equipment::query()->count());
    }

    public function test_type_is_set_from_the_model_that_creates_the_product()
    {
        $coffee = Coffee::factory()->create();
        $equipment = Equipment::factory()->create();

        $this->assertSame(ProductType::Coffee, $coffee->refresh()->type);
        $this->assertSame(ProductType::Equipment, $equipment->refresh()->type);
    }

    public function test_type_survives_when_model_events_are_disabled()
    {
        // Сидеры и Model::withoutEvents отключают события. Тип обязан
        // проставляться и без них, иначе товар уедет в базу без типа.
        $coffee = Model::withoutEvents(fn () => Coffee::query()->create([
            'slug' => 'quiet-roast',
            'name' => 'Quiet Roast',
        ]));

        $this->assertSame(ProductType::Coffee, $coffee->refresh()->type);
    }

    public function test_type_cannot_be_changed_by_mass_assignment()
    {
        $coffee = Coffee::factory()->create();

        $coffee->fill(['type' => ProductType::Equipment])->save();

        $this->assertSame(ProductType::Coffee, $coffee->refresh()->type);
    }

    public function test_variants_are_linked_by_product_id_for_child_models()
    {
        // У наследников связь вывела бы внешний ключ из имени класса и
        // искала coffee_id — ключ указан явно.
        $coffee = Coffee::factory()->create();

        $coffee->variants()->create([
            'title' => '250 г',
            'price' => 70_000,
            'weight_g' => 250,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $coffee->id,
            'title' => '250 г',
            'price' => 70_000,
        ]);
    }

    public function test_price_is_stored_in_whole_kopecks()
    {
        $variant = ProductVariant::factory()->for(Coffee::factory(), 'product')->create([
            'price' => 109_250,
        ]);

        $this->assertIsInt($variant->refresh()->price);
        $this->assertSame(109_250, $variant->price);
    }

    public function test_hidden_products_are_left_out_of_the_storefront()
    {
        Coffee::factory()->create();
        Coffee::factory()->hidden()->create();

        $this->assertSame(2, Coffee::query()->count());
        $this->assertSame(1, Coffee::query()->visible()->count());
    }

    public function test_related_products_can_cross_types()
    {
        $coffee = Coffee::factory()->create();
        $cezve = Equipment::factory()->create();

        $coffee->related()->attach($cezve->id, ['sort' => 0]);

        $this->assertTrue($coffee->related()->pluck('products.id')->contains($cezve->id));
    }

    public function test_only_coffee_can_be_subscribed_to_and_needs_a_grind()
    {
        $this->assertTrue(ProductType::Coffee->subscribable());
        $this->assertTrue(ProductType::Coffee->requiresGrind());

        $this->assertFalse(ProductType::Equipment->subscribable());
        $this->assertFalse(ProductType::Equipment->requiresGrind());
    }
}
