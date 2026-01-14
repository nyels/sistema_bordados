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
        Schema::table('products', function (Blueprint $table) {
            // Precio base del producto (precio de venta final)
            $table->decimal('base_price', 15, 4)->nullable()->after('specifications');

            // Tiempo estimado de producción en días
            $table->unsignedInteger('production_lead_time')->nullable()->after('base_price');

            // Costo total de producción (materiales + bordados + mano obra + extras)
            $table->decimal('production_cost', 15, 4)->nullable()->after('production_lead_time');

            // Margen de ganancia aplicado (%)
            $table->decimal('profit_margin', 5, 2)->nullable()->after('production_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'production_lead_time', 'production_cost', 'profit_margin']);
        });
    }
};
