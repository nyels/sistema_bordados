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
        Schema::create('product_extras', function (Blueprint $table) {
            $table->id();
            //   $table->foreignId('company_id')->constrained();
            $table->uuid('uuid')->unique();
            $table->string('name'); // Ej: "Encaje de Algodón", "Alforza Triple"
            $table->decimal('cost_addition', 15, 4)->default(0); // Cuánto suma al costo
            $table->decimal('price_addition', 15, 4)->default(0);
            $table->integer('minutes_addition')->default(0); // Tiempo extra de mano de obra
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_extras');
    }
};
