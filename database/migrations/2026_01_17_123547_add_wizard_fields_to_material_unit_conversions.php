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
        Schema::table('material_unit_conversions', function (Blueprint $table) {
            $table->foreignId('intermediate_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->decimal('intermediate_qty', 15, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_unit_conversions', function (Blueprint $table) {
            $table->dropForeign(['intermediate_unit_id']);
            $table->dropColumn(['intermediate_unit_id', 'intermediate_qty']);
        });
    }
};
