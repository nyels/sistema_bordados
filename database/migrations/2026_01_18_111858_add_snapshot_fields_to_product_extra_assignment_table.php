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
        Schema::table('product_extra_assignment', function (Blueprint $table) {
            $table->decimal('snapshot_cost', 10, 2)->after('product_extra_id')->default(0)->comment('Costo congelado al momento de la asignación');
            $table->decimal('snapshot_price', 10, 2)->after('snapshot_cost')->default(0)->comment('Precio congelado al momento de la asignación');
            $table->integer('snapshot_time')->after('snapshot_price')->default(0)->comment('Tiempo congelado en minutos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_extra_assignment', function (Blueprint $table) {
            $table->dropColumn(['snapshot_cost', 'snapshot_price', 'snapshot_time']);
        });
    }
};
