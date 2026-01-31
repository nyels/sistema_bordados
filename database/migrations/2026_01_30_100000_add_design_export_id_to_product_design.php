<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega design_export_id a product_design para vincular directamente
     * el export específico que se seleccionó al dar de alta el producto.
     */
    public function up(): void
    {
        Schema::table('product_design', function (Blueprint $table) {
            $table->foreignId('design_export_id')
                ->nullable()
                ->after('design_id')
                ->constrained('design_exports')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_design', function (Blueprint $table) {
            $table->dropForeign(['design_export_id']);
            $table->dropColumn('design_export_id');
        });
    }
};
