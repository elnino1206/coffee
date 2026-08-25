<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();

            // Покупатель необязателен: оформить заказ можно без входа, и
            // тогда контакты живут на самом заказе.
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            $table->string('contact_name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();

            $table->string('ship_method');
            $table->string('address')->nullable();
            $table->unsignedInteger('delivery_price')->default(0);

            $table->string('pay_method');
            $table->text('comment')->nullable();

            $table->string('status');
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index(['status', 'placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
