<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Indica si el item fue agregado después de la creación original
            $table->boolean('is_annex')->default(false)->after('customization_notes');

            // Fecha en que se anexó (null si es item original)
            $table->timestamp('annexed_at')->nullable()->after('is_annex');

            // Índice para consultas de items anexos
            $table->index(['order_id', 'is_annex']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'is_annex']);
            $table->dropColumn(['is_annex', 'annexed_at']);
        });
    }
};
