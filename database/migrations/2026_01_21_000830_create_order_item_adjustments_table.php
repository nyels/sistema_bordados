<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_adjustments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relaciones
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('design_export_id')->nullable()->constrained('design_exports')->onDelete('set null');

            // Tipo de ajuste
            $table->enum('type', ['design', 'material', 'labor', 'other'])->default('design');

            // Montos
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('real_cost', 10, 2)->nullable();

            // Estado del ajuste
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Metadata
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['order_item_id', 'status']);
            $table->index(['design_export_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_adjustments');
    }
};
