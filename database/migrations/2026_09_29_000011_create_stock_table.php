<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('reserved_quantity', 15, 3)->default(0);
            $table->decimal('damaged_quantity', 15, 3)->default(0);
            $table->decimal('avg_cost', 15, 2)->default(0)->comment('Weighted average cost');
            $table->decimal('stock_value', 15, 2)->default(0)->comment('quantity * avg_cost, updated on stock change');
            $table->timestamps();
            $table->unique(['branch_id', 'product_id']);
            $table->index(['business_id', 'product_id']);
            $table->index(['branch_id', 'quantity']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock'); }
};
