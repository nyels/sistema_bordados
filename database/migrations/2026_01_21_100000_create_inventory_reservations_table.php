<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('material_variant_id')->constrained('material_variants')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->enum('status', ['reserved', 'released', 'consumed'])->default('reserved');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['material_variant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
