<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Подписчики рассылки. Не путать с подписками на кофе: та про
        // регулярные доставки, эта — про письма.
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();

            // Откуда пришёл адрес: подвал главной, страница подписки и так
            // далее. Без этого нельзя понять, какая форма работает.
            $table->string('source')->nullable();

            // Подтверждение по ссылке из письма появится вместе с почтовым
            // отправителем. Колонка заводится сразу: добавить её в живую
            // таблицу дороже, чем внести пустой.
            $table->timestamp('confirmed_at')->nullable();

            // Отписка не удаляет запись: адрес, удалённый и заведённый
            // заново, снова начнёт получать письма.
            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
