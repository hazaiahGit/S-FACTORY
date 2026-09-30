<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Bill of Materials (recipes)
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('Finished product');
            $table->string('name', 200);
            $table->string('version', 20)->default('1.0');
            $table->decimal('expected_output', 15, 3)->comment('Expected quantity produced');
            $table->foreignId('output_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'product_id']);
        });

        // BOM items (ingredients/materials)
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete()->comment('Raw material product');
            $table->string('item_type', 20)->default('material'); // material, labour, overhead
            $table->string('description', 200)->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            $table->index(['bom_id']);
        });

        // Production Orders
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('Product being manufactured');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('production_number', 50)->unique();
            $table->string('status', 20)->default('draft');
            // draft, planned, in_progress, completed, approved, cancelled
            $table->decimal('planned_quantity', 15, 3);
            $table->decimal('actual_quantity', 15, 3)->nullable();
            $table->decimal('waste_quantity', 15, 3)->nullable()->default(0);
            $table->date('planned_date');
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->decimal('total_material_cost', 15, 2)->default(0);
            $table->decimal('total_labour_cost', 15, 2)->default(0);
            $table->decimal('total_overhead_cost', 15, 2)->default(0);
            $table->decimal('total_production_cost', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->nullable()->comment('Cost per produced unit');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'branch_id', 'status']);
            $table->index(['product_id']);
        });

        // Actual materials consumed in production
        Schema::create('production_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type', 20)->default('material'); // material, labour, overhead
            $table->string('description', 200)->nullable();
            $table->decimal('planned_quantity', 15, 3)->nullable();
            $table->decimal('actual_quantity', 15, 3)->nullable();
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['production_order_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('production_materials');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
