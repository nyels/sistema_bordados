<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2.6-MIN: Campos fiscales pasivos para clientes.
 *
 * REGLA SELLADA:
 * - Solo estructura, sin validaciones
 * - No se modifica lógica existente
 * - Campos opcionales para facturación futura
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // RFC: Registro Federal de Contribuyentes (13 caracteres máx)
            $table->string('rfc', 13)->nullable()->after('email');

            // Razón social para facturación
            $table->string('razon_social', 255)->nullable()->after('rfc');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['rfc', 'razon_social']);
        });
    }
};
