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
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purchase_number', 50)->unique();
            $table->string('reference', 100)->nullable()->comment('Supplier invoice number');
            $table->string('status', 20)->default('received');
            // status: draft, ordered, partial, received, returned, cancelled
            $table->string('payment_status', 20)->default('unpaid');
            // payment_status: unpaid, partial, paid
            $table->date('transaction_date');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('transport_cost', 15, 2)->default(0);
            $table->decimal('other_costs', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->decimal('landed_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('delete_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'branch_id', 'transaction_date']);
            $table->index(['business_id', 'supplier_id']);
            $table->index(['status', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
