<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campo design_export_id a la tabla orders.
 * Permite vincular un archivo de producción (DesignExport) aprobado
 * directamente al pedido para producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('design_export_id')
                ->nullable()
                ->after('client_measurement_id')
                ->constrained('design_exports')
                ->nullOnDelete();

            $table->index('design_export_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['design_export_id']);
            $table->dropIndex(['design_export_id']);
            $table->dropColumn('design_export_id');
        });
    }
};
