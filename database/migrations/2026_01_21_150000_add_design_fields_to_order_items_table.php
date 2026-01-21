<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos para gestión de diseños/personalización en items de pedido
 * - design_file: Ruta al archivo de diseño subido (AI, DST, PNG, PDF)
 * - design_original_name: Nombre original del archivo
 * - design_status: Estado del diseño (pending, in_review, approved, rejected)
 * - design_notes: Notas del cliente sobre el diseño
 * - custom_text: Texto personalizado (nombre, iniciales, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Archivo de diseño
            $table->string('design_file')->nullable()->after('design_approved_by');
            $table->string('design_original_name')->nullable()->after('design_file');

            // Estado del diseño: pending, in_review, approved, rejected
            $table->string('design_status')->default('pending')->after('design_original_name');

            // Notas del cliente sobre el diseño
            $table->text('design_notes')->nullable()->after('design_status');

            // Texto personalizado (complementario a embroidery_text)
            $table->string('custom_text')->nullable()->after('design_notes');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'design_file',
                'design_original_name',
                'design_status',
                'design_notes',
                'custom_text',
            ]);
        });
    }
};
