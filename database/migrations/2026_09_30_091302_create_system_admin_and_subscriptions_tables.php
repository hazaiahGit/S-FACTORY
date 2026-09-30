<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create subscription_packages table
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('duration_days')->default(30);
            $table->integer('max_users')->nullable()->comment('Null means unlimited');
            $table->integer('max_branches')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add subscription fields to businesses
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('subscription_package_id')->nullable()->constrained('subscription_packages')->nullOnDelete();
            $table->date('subscription_ends_at')->nullable();
            $table->string('subscription_status', 30)->default('active'); // active, expired, suspended
        });

        // 3. Add is_system_admin to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system_admin')->default(false)->after('business_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_system_admin');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['subscription_package_id']);
            $table->dropColumn(['subscription_package_id', 'subscription_ends_at', 'subscription_status']);
        });

        Schema::dropIfExists('subscription_packages');
    }
};
