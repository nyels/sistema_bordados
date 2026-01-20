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
        Schema::table('orders', function (Blueprint $table) {
            // Campo para indicar si requiere factura (IVA)
            $table->boolean('requires_invoice')->default(false)->after('discount');
            // Monto del IVA (16%)
            $table->decimal('iva_amount', 12, 2)->default(0)->after('requires_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['requires_invoice', 'iva_amount']);
        });
    }
};
