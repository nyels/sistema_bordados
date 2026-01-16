<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar campos a la tabla materials
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('base_unit_id')->nullable()->after('material_category_id')->constrained('units');
            $table->foreignId('consumption_unit_id')->nullable()->after('base_unit_id')->constrained('units');
            $table->decimal('conversion_factor', 10, 4)->default(1.0000)->after('consumption_unit_id');
            $table->boolean('has_color')->default(true)->after('description');
        });

        // 2. Migrar datos existentes
        $categories = DB::table('material_categories')->get();
        foreach ($categories as $cat) {
            DB::table('materials')
                ->where('material_category_id', $cat->id)
                ->update([
                    'base_unit_id' => $cat->base_unit_id,
                    'consumption_unit_id' => $cat->base_unit_id, // Default 1:1
                    'has_color' => $cat->has_color ?? true,
                    'conversion_factor' => 1.0
                ]);
        }

        // Make fields not nullable if desired, but we'll leave them nullable for flexibility unless strictly required.
        // Actually, for data integrity, base_unit_id SHOULD be required. But modifying column in existing table with data can be tricky if some rows weren't updated. 
        // Assuming all materials have a category, they are updated.

        // 3. Eliminar campos de material_categories
        Schema::table('material_categories', function (Blueprint $table) {
            // Drop foreign key first. Note: Name is usually table_column_foreign
            $table->dropForeign(['base_unit_id']);
            $table->dropColumn(['base_unit_id', 'has_color']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restaurar campos en material_categories
        Schema::table('material_categories', function (Blueprint $table) {
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->restrictOnDelete();
            $table->boolean('has_color')->default(true);
        });

        // 2. Intentar restaurar datos (best effort)
        // Tomamos la unidad del primer material asociado a la categoría
        $categories = DB::table('material_categories')->get();
        foreach ($categories as $cat) {
            $firstMat = DB::table('materials')->where('material_category_id', $cat->id)->first();
            if ($firstMat) {
                DB::table('material_categories')
                    ->where('id', $cat->id)
                    ->update([
                        'base_unit_id' => $firstMat->base_unit_id,
                        'has_color' => $firstMat->has_color
                    ]);
            }
        }

        // 3. Eliminar campos de materials
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
            $table->dropForeign(['consumption_unit_id']);
            $table->dropColumn(['base_unit_id', 'consumption_unit_id', 'conversion_factor', 'has_color']);
        });
    }
};
