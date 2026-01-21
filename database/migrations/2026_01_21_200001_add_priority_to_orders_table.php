<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Prioridad numérica para ordenamiento en cola de producción
            // Menor número = mayor prioridad
            $table->unsignedTinyInteger('priority')->default(50)->after('urgency_level');
            $table->index(['status', 'priority', 'promised_date']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority', 'promised_date']);
            $table->dropColumn('priority');
        });
    }
};
