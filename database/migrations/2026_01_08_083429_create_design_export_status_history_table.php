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
        Schema::create('design_export_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_export_id')->constrained('design_exports')->onDelete('cascade');
            $table->string('previous_status')->nullable(); // null para creación inicial
            $table->string('new_status');
            $table->foreignId('changed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índice para búsquedas rápidas
            $table->index(['design_export_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_export_status_history');
    }
};
