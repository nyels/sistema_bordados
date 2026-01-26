<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Infraestructura mínima de stock v2.
     * Tabla para órdenes de producción para stock (sin cliente).
     */
    public function up(): void
    {
        Schema::create('stock_productions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('production_number')->unique();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('quantity_completed', 14, 4)->default(0);
            $table->enum('status', ['draft', 'in_production', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_productions');
    }
};
