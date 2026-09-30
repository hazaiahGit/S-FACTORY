<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_number', 50)->unique();
            $table->string('status', 20)->default('completed'); // pending, approved, completed, rejected
            $table->string('reason', 50)->nullable(); // wrong_item, damaged, changed_mind, wrong_qty, other
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('refund_method', 30)->nullable();
            $table->date('transaction_date');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['sale_id']);
            $table->index(['business_id', 'transaction_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('sales_returns'); }
};
