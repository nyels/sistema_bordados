<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Costos estimados vs reales
            $table->decimal('estimated_extras_cost', 10, 2)->default(0)->after('total');
            $table->decimal('real_extras_cost', 10, 2)->nullable()->after('estimated_extras_cost');

            // Estado de ajustes: tiene ajustes por aprobar
            $table->boolean('has_pending_adjustments')->default(false)->after('status');

            // Total final con ajustes aprobados
            $table->decimal('final_total', 10, 2)->nullable()->after('has_pending_adjustments');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_extras_cost',
                'real_extras_cost',
                'has_pending_adjustments',
                'final_total',
            ]);
        });
    }
};
