<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();

            // Помол — у кофе, и он же различает строки: один сорт в
            // разном помоле это две позиции, а не дубль.
            $table->string('grind')->nullable();

            $table->unsignedInteger('qty');
            $table->timestamps();

            // Цена в строке не хранится: в корзине она всегда текущая,
            // снимок делается при оформлении заказа.
            $table->index(['cart_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
