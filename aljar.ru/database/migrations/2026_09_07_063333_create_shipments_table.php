<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Отгрузка отделена от заказа по той же причине, что и платёж:
        // у неё свой номер у перевозчика, своя цепочка статусов и свой
        // ответ, который нужно хранить целиком. Недоставленный заказ
        // отправляют повторно — это вторая отгрузка того же заказа, а не
        // правка полей первой.
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('provider');
            // У СДЭК два идентификатора: свой uuid, по которому идут
            // запросы, и номер накладной, который называют покупателю.
            $table->string('provider_uuid')->nullable();
            $table->string('track_number')->nullable();

            // Тариф решает, откуда и куда едет посылка (склад-склад,
            // дверь-дверь). Хранится числом перевозчика: расшифровка
            // лежит в настройках доставки.
            $table->unsignedSmallInteger('tariff_code')->nullable();

            $table->string('status');
            $table->timestamp('status_at')->nullable();

            // Сколько за перевозку выставил перевозчик. С доставкой,
            // которую заплатил покупатель (`orders.delivery_price`), они
            // не совпадают: бесплатная для покупателя доставка магазину
            // всё равно чего-то стоит.
            $table->unsignedInteger('cost')->nullable();

            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_uuid']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
