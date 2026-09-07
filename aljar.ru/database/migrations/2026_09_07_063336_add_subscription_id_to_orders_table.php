<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Каждая отгрузка по подписке порождает обычный заказ со ссылкой
        // на неё. Иначе история в кабинете покажет только разовые
        // покупки, а повтор в один клик не увидит регулярные.
        //
        // Подписку можно удалить только вместе с покупателем, но заказ
        // при этом остаётся документом: ссылка обнуляется, а не тянет
        // заказ за собой.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });
    }
};
