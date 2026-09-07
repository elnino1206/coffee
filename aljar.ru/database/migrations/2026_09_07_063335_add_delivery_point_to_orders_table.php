<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Выбор пункта выдачи делает покупатель при оформлении, поэтому
        // он лежит на заказе, а не на отгрузке: отгрузки может ещё не
        // быть, а везти уже куда-то нужно.
        Schema::table('orders', function (Blueprint $table) {
            // Код города у перевозчика. Адрес строкой перевозчику не
            // годится: тариф и список пунктов выдачи считаются по коду.
            $table->unsignedInteger('city_code')->nullable();
            $table->string('city')->nullable();
            $table->string('delivery_point_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['city_code', 'city', 'delivery_point_code']);
        });
    }
};
