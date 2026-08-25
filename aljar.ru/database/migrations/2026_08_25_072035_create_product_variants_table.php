<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Заголовок показывается покупателем как есть: «250 г»,
            // «500 мл, медь». Жёсткого поля веса недостаточно —
            // оборудование различается объёмом и материалом.
            $table->string('title');

            // Копейки целым числом: дробные рубли дают расхождение в
            // сумме заказа, и находит его всегда покупатель.
            $table->unsignedInteger('price');

            $table->string('sku')->nullable();
            $table->unsignedInteger('stock')->default(0);

            // Вес отдельной колонкой, а не строкой в заголовке: по нему
            // считает цех, и он не должен разбирать текст.
            $table->unsignedSmallInteger('weight_g')->nullable();
            $table->unsignedSmallInteger('volume_ml')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);

            $table->string('moysklad_id')->nullable()->unique();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
