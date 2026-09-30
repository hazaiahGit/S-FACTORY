<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('movement_type', 30);
            // movement_type: purchase, sale, sale_return, purchase_return,
            // stock_adjustment, stock_transfer_in, stock_transfer_out,
            // damage, loss, production_consumption, production_output, opening_stock
            $table->string('reference_type', 50)->nullable()->comment('Morph type');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Morph ID');
            $table->string('reference_number', 50)->nullable();
            $table->decimal('quantity_before', 15, 3);
            $table->decimal('quantity_change', 15, 3)->comment('Positive=in, Negative=out');
            $table->decimal('quantity_after', 15, 3);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
            $table->index(['business_id', 'product_id', 'transaction_date']);
            $table->index(['branch_id', 'product_id']);
            $table->index(['movement_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock_movements'); }
};
