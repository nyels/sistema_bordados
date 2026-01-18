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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('materials_cost', 15, 6)->nullable()->after('production_cost');
            $table->decimal('embroidery_cost', 15, 6)->nullable()->after('materials_cost');
            $table->decimal('labor_cost', 15, 6)->nullable()->after('embroidery_cost');
            $table->decimal('extra_services_cost', 15, 6)->nullable()->after('labor_cost');
            $table->decimal('suggested_price', 15, 6)->nullable()->after('extra_services_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'materials_cost',
                'embroidery_cost',
                'labor_cost',
                'extra_services_cost',
                'suggested_price'
            ]);
        });
    }
};
