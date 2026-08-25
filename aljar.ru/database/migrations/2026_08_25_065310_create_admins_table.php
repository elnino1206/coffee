<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // Каналы уведомлений задаются на сотруднике, а не списком в
            // конфиге: увольнение не должно требовать выкладки.
            $table->string('telegram_chat_id')->nullable();
            $table->boolean('notify_orders')->default(true);
            $table->boolean('notify_leads')->default(true);

            // Доступ отзывается флагом, а не удалением записи: за
            // сотрудником остаются его правки в журнале.
            $table->boolean('is_active')->default(true);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
