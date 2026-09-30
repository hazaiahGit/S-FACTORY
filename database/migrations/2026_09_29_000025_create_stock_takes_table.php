<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stock_take_number', 50)->unique();
            $table->string('type', 20)->default('full'); // full, category, selected
            $table->string('status', 20)->default('draft'); // draft, in_progress, completed, approved, cancelled
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_variance_value', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'branch_id']);
        });

        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('system_quantity', 15, 3);
            $table->decimal('physical_quantity', 15, 3)->nullable();
            $table->decimal('variance', 15, 3)->nullable()->comment('physical_quantity - system_quantity, computed on save');
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('variance_value', 15, 2)->nullable();
            $table->string('status', 20)->default('pending'); // pending, counted, approved
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['stock_take_id']);
            $table->index(['product_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('stock_take_items');
        Schema::dropIfExists('stock_takes');
    }
};
