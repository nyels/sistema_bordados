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
        Schema::create('design_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->onDelete('cascade');

            // Identidad de negocio
            $table->string('sku')->unique();

            // Datos operativos
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock')->default(0);

            // Estado de negocio
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // Auditoría
            $table->timestamps();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();

            // Eliminación lógica (SaaS / Historial)
            $table->softDeletes();

            // Índices
            $table->index('design_id');
            $table->index('sku');
            $table->index('is_active');
        });

        /*
        decimal('price', 10, 2): Permite precios como 1234.56 (10 dígitos, 2 decimales)
        constrained(): Sin especificar tabla, Laravel deduce 'designs' por el nombre del
         */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('design_variants');
        Schema::enableForeignKeyConstraints();
    }
};
