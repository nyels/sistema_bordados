<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nivel de urgencia para priorización de producción
            $table->enum('urgency_level', ['normal', 'urgente', 'express'])
                ->default('normal')
                ->after('status');

            // Fecha mínima calculada según tiempos de producción
            $table->date('minimum_date')->nullable()->after('promised_date');

            // Soporte para pedidos anexo (modificaciones post-producción)
            $table->foreignId('order_parent_id')
                ->nullable()
                ->after('id')
                ->constrained('orders')
                ->nullOnDelete();

            // Índice para consultas de anexos
            $table->index(['order_parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['order_parent_id']);
            $table->dropIndex(['order_parent_id']);
            $table->dropColumn(['urgency_level', 'minimum_date', 'order_parent_id']);
        });
    }
};
