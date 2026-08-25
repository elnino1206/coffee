<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Тип определяет набор характеристик и правила: можно ли
            // подписаться, нужен ли помол. Категория — только навигация.
            $table->string('type');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('slug')->unique();

            // Названий два: витринное короткое принадлежит сайту, полное
            // учётное приезжает из МойСклада.
            $table->string('name');
            $table->string('full_name')->nullable();

            $table->string('image_path')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('wholesale_available')->default(false);

            // Кэш отзывов: пересчитывается при модерации, а не считается
            // на каждой карточке каталога.
            $table->decimal('rating_avg', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);

            // Крепления под синхронизацию. Заводятся сразу: добавить
            // колонку в живую таблицу дороже, чем внести её пустой.
            $table->string('moysklad_id')->nullable()->unique();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index(['type', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
