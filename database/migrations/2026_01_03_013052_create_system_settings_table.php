<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json, select
            $table->string('group', 50)->default('general'); // general, inventario, facturacion, produccion
            $table->string('label', 100);
            $table->string('description', 255)->nullable();
            $table->json('options')->nullable(); // Para tipo select
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('group');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
