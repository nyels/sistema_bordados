<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_measurement_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Referencias
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Medidas como JSON (flexibilidad para diferentes tipos de producto)
            $table->json('measurements');

            // Metadata
            $table->string('source')->default('order'); // order, manual, import
            $table->text('notes')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('captured_at');
            $table->timestamps();

            // Índices
            $table->index(['cliente_id', 'captured_at']);
            $table->index('order_id');
            $table->index('order_item_id');
        });

        // Agregar referencia en order_items al historial
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('measurement_history_id')
                  ->nullable()
                  ->after('measurements')
                  ->constrained('client_measurement_history')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['measurement_history_id']);
            $table->dropColumn('measurement_history_id');
        });

        Schema::dropIfExists('client_measurement_history');
    }
};
