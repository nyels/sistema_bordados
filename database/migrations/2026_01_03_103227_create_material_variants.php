<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('color', 50)->nullable();
            $table->string('sku', 30)->unique();
            $table->decimal('current_stock', 12, 4)->default(0);
            $table->decimal('min_stock_alert', 12, 4)->default(0);
            $table->decimal('current_value', 14, 4)->default(0);
            $table->decimal('average_cost', 14, 4)->default(0);
            $table->decimal('last_purchase_cost', 14, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
            $table->index('sku');
            $table->index('material_id');
            $table->index(['material_id', 'color']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_variants');
    }
};
