<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number', 50)->unique();
            $table->string('lot_number', 50)->nullable();
            $table->date('purchase_date');
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity_received', 15, 3);
            $table->decimal('quantity_remaining', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->string('reference', 50)->nullable()->comment('Purchase invoice number');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'product_id']);
            $table->index(['business_id', 'batch_number']);
        });
    }
    public function down(): void { Schema::dropIfExists('product_batches'); }
};
