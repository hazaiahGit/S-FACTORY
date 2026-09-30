<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['business_id']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('expense_number', 50)->unique();
            $table->string('title', 200);
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->date('expense_date');
            $table->string('reference', 100)->nullable();
            $table->string('attachment')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('approved'); // pending, approved, rejected
            $table->text('delete_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'branch_id', 'expense_date']);
            $table->index(['expense_category_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
