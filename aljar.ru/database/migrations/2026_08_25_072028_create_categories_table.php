<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Категория живёт внутри своего типа: «Турки» относятся к
            // оборудованию и в разделе кофе не показываются.
            $table->string('type');
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['type', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
