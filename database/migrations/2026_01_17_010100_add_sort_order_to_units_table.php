<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('units', 'sort_order')) {
            Schema::table('units', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('default_conversion_factor');
            });
        }
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
