<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar campo parent_message_id para hilos de conversación
     */
    public function up(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            $table->foreignId('parent_message_id')
                  ->nullable()
                  ->after('order_id')
                  ->constrained('order_messages')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropForeign(['parent_message_id']);
            $table->dropColumn('parent_message_id');
        });
    }
};
