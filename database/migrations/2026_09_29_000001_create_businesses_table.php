<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 20)->unique()->nullable();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('business_type')->default('hardware_store'); // hardware_store, factory, both
            $table->string('currency', 10)->default('TZS');
            $table->string('currency_symbol', 10)->default('TZS');
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('Africa/Dar_es_Salaam');
            $table->string('costing_method', 30)->default('weighted_average'); // weighted_average, fifo
            $table->boolean('tax_enabled')->default(false);
            $table->boolean('tax_inclusive')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(18.00);
            $table->string('invoice_prefix', 20)->default('INV');
            $table->string('po_prefix', 20)->default('PO');
            $table->string('receipt_prefix', 20)->default('RCP');
            $table->string('batch_prefix', 20)->default('HW');
            $table->boolean('negative_stock_allowed')->default(false);
            $table->boolean('require_delete_reason')->default(true);
            $table->boolean('approval_required_for_adjustments')->default(true);
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
