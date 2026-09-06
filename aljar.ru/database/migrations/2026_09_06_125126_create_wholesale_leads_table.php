<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Оптовые заявки идут отдельным потоком от розничных обращений —
        // требование ТЗ 5.4: у них другой набор полей и другой адресат,
        // иначе лиды теряются среди вопросов о доставке.
        Schema::create('wholesale_leads', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();

            $table->string('name');
            $table->string('company');
            $table->string('phone');
            $table->string('email')->nullable();

            $table->string('business_type');
            $table->string('volume')->nullable();
            $table->text('comment')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();

            $table->string('status');
            $table->text('manager_comment')->nullable();

            // Согласие на обработку хранится отметкой времени, а не
            // флагом: доказательством служит момент, когда галочка была
            // поставлена.
            $table->timestamp('consent_at');

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_leads');
    }
};
