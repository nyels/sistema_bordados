<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de categorías de extras
        Schema::create('extra_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Agregar FK a product_extras
        Schema::table('product_extras', function (Blueprint $table) {
            $table->foreignId('extra_category_id')->nullable()->after('id')->constrained('extra_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_extras', function (Blueprint $table) {
            $table->dropForeign(['extra_category_id']);
            $table->dropColumn('extra_category_id');
        });

        Schema::dropIfExists('extra_categories');
    }
};
