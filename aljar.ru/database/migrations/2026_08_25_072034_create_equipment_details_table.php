<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_details', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();

            $table->string('brand')->nullable();
            $table->string('material')->nullable();
            $table->string('country')->nullable();
            $table->unsignedSmallInteger('warranty_months')->nullable();

            // Совместимость с плитой — то, из-за чего турку возвращают.
            $table->boolean('gas_compatible')->default(true);
            $table->boolean('electric_compatible')->default(true);
            $table->boolean('induction_compatible')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_details');
    }
};
