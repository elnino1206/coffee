<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Идентификатор для повторных списаний хранится на покупателе, а
        // не на подписке: карта одна, подписок у неё может быть
        // несколько, и привязывать карту заново на каждую — значит
        // просить покупателя платить вручную.
        Schema::table('customers', function (Blueprint $table) {
            $table->string('rebill_id')->nullable();
            // Маска нужна кабинету: «карта •• 4242» — единственный способ
            // показать, с чего именно списывают, не храня номер.
            $table->string('card_mask')->nullable();
            $table->timestamp('card_bound_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['rebill_id', 'card_mask', 'card_bound_at']);
        });
    }
};
