<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('purchase_number', 20)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedors')->restrictOnDelete();
            $table->string('status', 20)->default('borrador');
            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 14, 4)->default(0);
            $table->decimal('discount_amount', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('reference', 100)->nullable();
            $table->date('ordered_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('proveedor_id');
            $table->index('created_by');
            $table->index('ordered_at');
            $table->index('activo');
            $table->index(['status', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
