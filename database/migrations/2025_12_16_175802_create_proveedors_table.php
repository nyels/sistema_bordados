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
        Schema::create('proveedors', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_proveedor');
            $table->string('tipo_proveedor')->nullable();
            $table->string('direccion')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('ciudad')->nullable();
            $table->string('nombre_contacto')->nullable();
            $table->string('telefono_contacto')->nullable();
            $table->string('email_contacto')->nullable();

            $table->boolean('activo')->default(true);
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('proveedors');
        Schema::enableForeignKeyConstraints();
    }
};
