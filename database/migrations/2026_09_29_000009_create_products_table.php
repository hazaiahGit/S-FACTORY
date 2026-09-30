<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku', 50)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->text('description')->nullable();
            $table->string('product_type', 30)->default('purchased'); // purchased, manufactured, both
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0)->comment('Weighted avg cost');
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('wholesale_price', 15, 2)->nullable();
            $table->decimal('min_selling_price', 15, 2)->nullable();
            $table->decimal('min_stock', 10, 3)->default(0);
            $table->decimal('reorder_level', 10, 3)->default(0);
            $table->decimal('opening_stock', 10, 3)->default(0);
            $table->boolean('track_stock')->default(true);
            $table->boolean('has_batches')->default(false);
            $table->boolean('has_expiry')->default(false);
            $table->boolean('tax_applicable')->default(false);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('batch_number_prefix', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'category_id']);
            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'barcode']);
            $table->index(['business_id', 'sku']);
            $table->unique(['business_id', 'slug']);
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
