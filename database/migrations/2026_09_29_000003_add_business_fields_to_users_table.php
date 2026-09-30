<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('employee_id', 30)->nullable()->after('avatar');
            $table->string('job_title', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->json('financial_visibility')->nullable()->comment('JSON of what financial data this user can see');
            $table->json('dashboard_widgets')->nullable()->comment('JSON of which dashboard widgets to show');
            $table->string('preferred_language', 10)->default('en');
            $table->softDeletes();
            $table->index(['business_id', 'is_active']);
            $table->index(['branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'business_id',
                'branch_id',
                'phone',
                'avatar',
                'employee_id',
                'job_title',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'financial_visibility',
                'dashboard_widgets',
                'preferred_language',
            ]);
        });
    }
};
