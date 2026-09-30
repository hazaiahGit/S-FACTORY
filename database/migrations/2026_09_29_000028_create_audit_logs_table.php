<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 100)->nullable()->comment('Snapshot of username');
            $table->string('action', 50);
            // create, update, delete, restore, approve, reject, login, logout, export
            $table->string('module', 50);
            // products, sales, purchases, inventory, customers, suppliers, manufacturing, etc.
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->index(['business_id', 'action', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['module', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('audit_logs');
    }
};
