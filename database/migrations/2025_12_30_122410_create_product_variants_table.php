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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->string('sku_variant')->unique(); // Ej: BLU-LIN-BLA-M
            $table->decimal('price', 15, 4)->default(0); // Precio específico de esta combinación

            // Metadata para almacenar los IDs de los atributos (Color: Azul, Tela: Lino)
            // Esto se vincula con tus tablas actuales 'attribute_values'
            $table->json('attribute_combinations')->nullable();

            $table->integer('stock_alert')->default(5); // Alerta de inventario bajo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
