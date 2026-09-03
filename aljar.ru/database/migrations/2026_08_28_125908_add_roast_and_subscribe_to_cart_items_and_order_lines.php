<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Обжарка и подписка выбираются при добавлении в корзину.
 *
 * Бренд обжаривает под заказ, поэтому степень — свойство покупки, а не
 * только каталожная характеристика зерна. Значение из карточки товара
 * идёт как выбранное по умолчанию.
 *
 * Обе колонки нужны и в строке заказа: заказ хранит снимок покупки, а не
 * ссылку на корзину, которую после оформления очищают.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('roast')->nullable()->after('grind');
            $table->boolean('subscribe')->default(false)->after('roast');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->string('roast')->nullable()->after('grind');
            $table->boolean('subscribe')->default(false)->after('roast');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['roast', 'subscribe']);
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn(['roast', 'subscribe']);
        });
    }
};
