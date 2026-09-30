<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Create product_types table
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->boolean('is_sold')->default(true)->comment('Available for sale in POS/Invoices');
            $table->boolean('is_purchased')->default(true)->comment('Available for supplier purchase orders');
            $table->boolean('is_manufactured')->default(false)->comment('Producible via BOM/Production');
            $table->boolean('track_stock')->default(true)->comment('Requires stock tracking');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'is_active']);
        });

        // 2. Add product_type_id to products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_type_id')->nullable()->after('supplier_id')->constrained('product_types')->nullOnDelete();
        });

        // 3. Add allow_decimal to units
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('allow_decimal')->default(true)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('allow_decimal');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_type_id']);
            $table->dropColumn('product_type_id');
        });

        Schema::dropIfExists('product_types');
    }
};
