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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Preparado para futuro SaaS/Sucursales (por ahora puede ser null o defecto 1)
            $table->unsignedBigInteger('tenant_id')->default(1)->index();

            // Relación con la categoría que creamos en el Paso 1
            $table->foreignId('product_category_id')->constrained()->onDelete('cascade');

            $table->string('name'); // Ej: Hipil Tradicional, Cosmetiquera Mezclilla
            $table->string('sku')->unique(); // Código único industrial
            $table->text('description')->nullable();

            // Metadata Industrial: Aquí guardamos tipo de tela base, material interior, etc.
            // Usamos JSON para tener flexibilidad total sin crear mil columnas.
            $table->json('specifications')->nullable();

            $table->enum('status', ['draft', 'active', 'discontinued'])->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
