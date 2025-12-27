<?php
// Nombre del archivo: 2025_12_25_000001_create_design_exports_table.php
// Ubicación: database/migrations/2025_12_25_000001_create_design_exports_table.php

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
        Schema::create('design_exports', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('design_id')->constrained()->onDelete('cascade');
            $table->foreignId('design_variant_id')->nullable()->constrained('design_variants')->onDelete('cascade');

            // Información de aplicación
            $table->string('application_type', 50)->default('general');
            $table->string('application_label', 100);
            $table->string('placement_description', 255)->nullable();

            // Archivo técnico
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_format', 10);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();

            // Datos técnicos
            $table->unsignedInteger('stitches_count')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();
            $table->unsignedInteger('colors_count')->nullable();
            $table->json('colors_detected')->nullable();

            // Estado
            $table->enum('status', ['borrador', 'pendiente', 'aprobado', 'archivado'])->default('borrador');
            $table->boolean('auto_read_success')->default(false);
            $table->text('notes')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('application_type_id')->nullable()->constrained('application_types')->nullOnDelete();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['design_id']);
            $table->index(['design_variant_id']);
            $table->index(['status']);
            $table->index(['application_type_id']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('design_exports');
        Schema::enableForeignKeyConstraints();
    }
};
