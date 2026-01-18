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
        // material_variants
        Schema::table('material_variants', function (Blueprint $table) {
            $table->decimal('average_cost', 16, 6)->change();
            $table->decimal('last_purchase_cost', 16, 6)->change();
            $table->decimal('current_value', 20, 6)->change();
        });

        // inventory_movements
        // Asumiendo que existen estas columnas basadas en las convenciones,
        // pero verificando una por una si falla.
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 16, 6)->change();
            $table->decimal('total_cost', 20, 6)->change();
            $table->decimal('average_cost_before', 16, 6)->change();
            $table->decimal('average_cost_after', 16, 6)->change();
            $table->decimal('value_before', 20, 6)->change();
            $table->decimal('value_after', 20, 6)->change();
        });

        // purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('unit_price', 16, 6)->change();
            $table->decimal('converted_unit_cost', 16, 6)->change();
            $table->decimal('subtotal', 20, 6)->change();
        });

        // purchase_reception_items
        Schema::table('purchase_reception_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 16, 6)->change();
        });

        // purchases (Columna 'total' en lugar de 'total_amount')
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 20, 6)->change();
            $table->decimal('tax_amount', 20, 6)->change();
            $table->decimal('discount_amount', 20, 6)->change();
            $table->decimal('total', 20, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_variants', function (Blueprint $table) {
            $table->decimal('average_cost', 15, 4)->change();
            $table->decimal('last_purchase_cost', 15, 4)->change();
            $table->decimal('current_value', 15, 4)->change();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->change();
            $table->decimal('total_cost', 15, 4)->change();
            $table->decimal('average_cost_before', 15, 4)->change();
            $table->decimal('average_cost_after', 15, 4)->change();
            $table->decimal('value_before', 15, 4)->change();
            $table->decimal('value_after', 15, 4)->change();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 4)->change();
            $table->decimal('converted_unit_cost', 15, 4)->change();
            $table->decimal('subtotal', 15, 4)->change();
        });

        Schema::table('purchase_reception_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 4)->change();
            $table->decimal('tax_amount', 15, 4)->change();
            $table->decimal('discount_amount', 15, 4)->change();
            $table->decimal('total', 15, 4)->change();
        });
    }
};
