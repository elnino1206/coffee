<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Подписка — самостоятельная сущность, а не состояние заказа: по
        // ТЗ 5.2 пауза и отмена это статусы, а при отмене показывается
        // удерживающее предложение, которое некуда прицепить, если
        // запись исчезает.
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();

            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            // Вариант может уйти из продажи, а подписка на него остаётся:
            // менеджеру нужно видеть, кому и что перестало приезжать.
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('grind')->nullable();

            $table->unsignedSmallInteger('frequency_weeks');
            $table->date('next_delivery_at')->nullable();

            $table->string('status');
            // Скидка фиксируется в момент оформления: снижение процента в
            // настройках не должно менять условия тем, кто подписался
            // раньше.
            $table->unsignedSmallInteger('discount_percent');

            $table->timestamp('paused_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            // Три неудачных списания подряд блокируют подписку (раздел 6
            // модели): счётчик обнуляется удачной оплатой.
            $table->unsignedTinyInteger('failed_charge_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'next_delivery_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
