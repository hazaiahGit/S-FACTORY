<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('collected_quantity', 15, 3)->default(0)->comment('Actually picked up');
            $table->decimal('returned_quantity', 15, 3)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0)->comment('COGS per unit');
            $table->string('price_type', 30)->default('retail');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2);
            $table->decimal('line_cogs', 15, 2)->default(0);
            $table->decimal('line_profit', 15, 2)->default(0);
            $table->string('fulfillment_status', 20)->default('pending');
            // pending, partial, fulfilled, returned
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['sale_id']);
            $table->index(['product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('sale_items'); }
};
