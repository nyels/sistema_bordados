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
        Schema::table('images', function (Blueprint $table) {
            $table->string('dominant_color', 10)->nullable()->after('is_primary');
            $table->json('color_palette')->nullable()->after('dominant_color');
            $table->string('original_extension', 10)->nullable()->after('color_palette');
            $table->string('correct_extension', 10)->nullable()->after('original_extension');
            $table->json('metadata')->nullable()->after('correct_extension');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['dominant_color', 'color_palette', 'original_extension', 'correct_extension', 'metadata']);
        });
    }
};
