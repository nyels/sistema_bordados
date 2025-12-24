<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('design_variant_images', function (Blueprint $table) {
            $table->id();

            // Relación con variante
            $table->foreignId('design_variant_id')
                ->constrained('design_variants')
                ->onDelete('cascade');

            // Archivo
            $table->string('path');           // storage path
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            // Control visual
            $table->boolean('is_primary')->default(false);
            $table->integer('order')->default(0);

            $table->timestamps();

            // Índices
            $table->index(['design_variant_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_variant_images');
    }
};
