<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === ITEMS DEL PEDIDO ===
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Producto (obligatorio)
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            // Variante (opcional - puede ser producto sin variantes)
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();

            // Snapshot de datos al momento del pedido (para auditoría)
            $table->string('product_name', 200); // Nombre al momento
            $table->string('variant_sku', 100)->nullable();
            $table->decimal('unit_price', 12, 2); // Precio al momento

            // Cantidad y totales
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 12, 2); // unit_price * quantity
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2); // subtotal - discount

            // Personalización
            $table->string('embroidery_text', 255)->nullable(); // Texto a bordar
            $table->text('customization_notes')->nullable(); // Notas de personalización

            // Estado individual del ítem
            $table->enum('status', [
                'pending',      // Pendiente
                'in_progress',  // En proceso
                'completed',    // Completado
                'cancelled'     // Cancelado
            ])->default('pending');

            $table->timestamps();

            // Índice para reportes de producción
            $table->index(['order_id', 'status']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
