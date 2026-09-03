<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Дата обжарки убрана из продукта: витрина больше не строит на ней
     * ни бейдж свежести, ни сортировку, и хранить её незачем.
     */
    public function up(): void
    {
        Schema::table('coffee_details', function (Blueprint $table) {
            $table->dropColumn('roast_date');
        });
    }

    public function down(): void
    {
        Schema::table('coffee_details', function (Blueprint $table) {
            $table->date('roast_date')->nullable();
        });
    }
};
