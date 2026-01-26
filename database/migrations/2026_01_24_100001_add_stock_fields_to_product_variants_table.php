<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Infraestructura mínima de stock v2.
     * Agrega campos de stock de productos terminados a product_variants.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('current_stock', 14, 4)->default(0)->after('stock_alert');
            $table->decimal('reserved_stock', 14, 4)->default(0)->after('current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['current_stock', 'reserved_stock']);
        });
    }
};
