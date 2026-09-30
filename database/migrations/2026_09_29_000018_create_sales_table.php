<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sale_number', 50)->unique();
            $table->string('sale_type', 20)->default('sale'); // sale, quotation, proforma
            $table->string('status', 30)->default('confirmed');
            // status: draft, confirmed, paid, partial, credit, fulfilled, partial_fulfilled, delivered, cancelled, returned
            $table->string('payment_status', 20)->default('paid');
            // payment_status: paid, partial, unpaid, credit, overpaid
            $table->string('fulfillment_status', 30)->default('fulfilled');
            // fulfillment_status: pending, processing, ready, partial, fulfilled, delivered, returned, cancelled
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->decimal('cogs', 15, 2)->default(0)->comment('Cost of goods sold');
            $table->decimal('gross_profit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('delete_reason')->nullable();
            $table->string('valid_until')->nullable()->comment('For quotations');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'branch_id', 'transaction_date']);
            $table->index(['business_id', 'customer_id']);
            $table->index(['status', 'payment_status']);
            $table->index(['business_id', 'sale_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('sales'); }
};
