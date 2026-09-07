<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Отдельная таблица, а не флаг `is_paid` на заказе: у платежа
        // своя жизнь и свои статусы, попыток по одному заказу может быть
        // несколько, а ответ провайдера нужно хранить целиком — по нему
        // разбирают спорные случаи.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Провайдер записан в строке, а не подразумевается: смена
            // эквайринга не должна означать, что прошлые платежи стали
            // выглядеть так, будто их провёл новый банк.
            $table->string('provider');
            $table->string('provider_payment_id')->nullable();

            // Сумма фиксируется на платеже: состав заказа может быть
            // поправлен менеджером, а списано было столько, сколько
            // списано.
            $table->unsignedInteger('amount');

            $table->string('status');
            $table->timestamp('paid_at')->nullable();

            // Привязка карты для регулярных списаний по подписке. Сам
            // идентификатор живёт на покупателе (карта одна, подписок
            // может быть несколько), здесь — признак, что платёж шёл с
            // привязкой.
            $table->boolean('binds_card')->default(false);

            $table->json('payload')->nullable();
            $table->timestamps();

            // Уведомление о платеже приходит не один раз: по паре
            // «провайдер + его номер» повтор узнаётся и не заводит
            // вторую запись.
            $table->unique(['provider', 'provider_payment_id']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
