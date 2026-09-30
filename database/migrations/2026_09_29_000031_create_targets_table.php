<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Specific salesperson/cashier');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('name');
            $table->string('target_type', 30); // sales, production, profit, purchase, collection, customer, product, expense, custom
            $table->decimal('target_value', 15, 2);
            $table->string('measurement_unit', 20)->default('amount'); // amount, quantity, count
            
            $table->string('period_type', 20); // daily, weekly, monthly, quarterly, yearly, custom
            $table->date('start_date');
            $table->date('end_date');
            
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['business_id', 'target_type']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('targets');
    }
};
