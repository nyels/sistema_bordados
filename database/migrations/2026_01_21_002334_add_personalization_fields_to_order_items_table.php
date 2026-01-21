<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Tipo de personalización calculado al crear item
            $table->enum('personalization_type', ['none', 'text', 'measurements', 'design'])
                ->default('none')
                ->after('customization_notes');

            // Estado del diseño (para items con personalización de diseño)
            $table->boolean('design_approved')->default(false)->after('personalization_type');
            $table->timestamp('design_approved_at')->nullable()->after('design_approved');
            $table->foreignId('design_approved_by')->nullable()->after('design_approved_at')
                ->constrained('users')->onDelete('set null');

            // Snapshot del multiplicador usado al momento de creación
            $table->decimal('time_multiplier_snapshot', 4, 2)->default(1.00)->after('design_approved_by');

            // Tiempo estimado calculado (días)
            $table->unsignedInteger('estimated_lead_time')->default(0)->after('time_multiplier_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['design_approved_by']);
            $table->dropColumn([
                'personalization_type',
                'design_approved',
                'design_approved_at',
                'design_approved_by',
                'time_multiplier_snapshot',
                'estimated_lead_time',
            ]);
        });
    }
};
