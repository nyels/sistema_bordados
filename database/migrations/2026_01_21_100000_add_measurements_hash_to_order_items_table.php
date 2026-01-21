<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Hash de medidas al momento de aprobación del diseño
            // Permite detectar cambios post-aprobación que bloquean producción
            $table->string('measurements_hash_at_approval', 32)
                ->nullable()
                ->after('estimated_lead_time');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('measurements_hash_at_approval');
        });
    }
};
