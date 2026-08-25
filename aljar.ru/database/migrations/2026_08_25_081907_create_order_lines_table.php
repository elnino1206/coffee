<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Ссылка на вариант нужна для повторного заказа и выгрузки в
            // учётную систему, но состав заказа держится не на ней.
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            // Снимок на момент покупки: без него прошлые заказы поедут
            // при первом же изменении прайса или переименовании товара.
            $table->string('product_name');
            $table->string('variant_title');
            $table->unsignedSmallInteger('weight_g')->nullable();
            $table->unsignedInteger('unit_price');

            $table->string('grind')->nullable();
            $table->unsignedInteger('qty');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
