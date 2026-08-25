<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // Корзина живёт в базе, а не в сессии: покупатель начинает на
            // телефоне и дополняет с компьютера. У гостя её держит токен
            // в куке, у покупателя — сам покупатель.
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('token')->unique();

            // Месяц от последнего изменения: корзина полугодовой давности
            // показывает не намерение, а забытый выбор.
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
