<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requestable_type', 100);
            $table->unsignedBigInteger('requestable_id');
            $table->string('action', 50);
            // delete, stock_adjustment, large_discount, price_change, debt_cancellation, stock_transfer, production
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('data')->nullable()->comment('Extra data needed for approval action');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'status']);
            $table->index(['requestable_type', 'requestable_id']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('approval_requests');
    }
};
