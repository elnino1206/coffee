<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coffee_details', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();

            $table->string('species');
            $table->string('origin');
            $table->string('region')->nullable();
            $table->string('process');
            $table->string('notes');

            $table->string('roast');
            $table->string('brew_method');

            // Дата обжарки — главный аргумент доверия по ТЗ. Свежесть из
            // неё считается, а не хранится.
            $table->date('roast_date')->nullable();

            // Пять шкал отдельными колонками: каталог фильтруется по
            // нескольким параметрам сразу, и по колонкам это дешевле.
            $table->unsignedTinyInteger('profile_fruity')->default(0);
            $table->unsignedTinyInteger('profile_chocolate')->default(0);
            $table->unsignedTinyInteger('profile_spice')->default(0);
            $table->unsignedTinyInteger('profile_body')->default(0);
            $table->unsignedTinyInteger('profile_acidity')->default(0);

            $table->timestamps();

            $table->index('roast');
            $table->index('brew_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coffee_details');
    }
};
