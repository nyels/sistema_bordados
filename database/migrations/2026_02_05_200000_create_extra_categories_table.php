<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // Insertar categoría por defecto
        $now = now();
        $defaultCategoryId = DB::table('extra_categories')->insertGetId([
            'nombre' => 'GENERAL',
            'activo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Agregar columna primero (sin FK)
        Schema::table('product_extras', function (Blueprint $table) {
            $table->unsignedBigInteger('extra_category_id')->after('id');
        });

        // Actualizar registros existentes con la categoría por defecto
        DB::table('product_extras')
            ->update(['extra_category_id' => $defaultCategoryId]);

        // Ahora agregar la FK
        Schema::table('product_extras', function (Blueprint $table) {
            $table->foreign('extra_category_id')
                ->references('id')
                ->on('extra_categories')
                ->cascadeOnDelete();
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
