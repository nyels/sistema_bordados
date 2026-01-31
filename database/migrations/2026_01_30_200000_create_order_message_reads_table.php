<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivot para tracking de lecturas de mensajes.
     * Cada registro indica que un usuario ha leído un mensaje específico.
     */
    public function up(): void
    {
        Schema::create('order_message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('order_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('read_at')->useCurrent();

            // Un usuario solo puede marcar un mensaje como leído una vez
            $table->unique(['message_id', 'user_id']);

            // Índices para consultas eficientes
            $table->index('user_id');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_message_reads');
    }
};
