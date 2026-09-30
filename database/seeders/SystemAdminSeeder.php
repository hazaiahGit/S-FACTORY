<?php

namespace Database\Seeders;

use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SystemAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Super Admin role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // 2. Create or update the Superadmin / System Administrator user
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@sfactory.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'is_system_admin' => true,
                'business_id' => null,
                'branch_id' => null,
                'job_title' => 'Global Platform Superadmin',
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // Also ensure admin@sfactory.com has superadmin & system admin access as fallback
        $admin = User::where('email', 'admin@sfactory.com')->first();
        if ($admin) {
            $admin->update([
                'password' => Hash::make('password'),
                'is_system_admin' => true,
                'is_active' => true,
            ]);
            $admin->assignRole($superAdminRole);
        } else {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@sfactory.com',
                'password' => Hash::make('password'),
                'is_system_admin' => true,
                'business_id' => null,
                'branch_id' => null,
                'job_title' => 'System Administrator',
                'is_active' => true,
            ]);
            $admin->assignRole($superAdminRole);
        }

        // 3. Seed starter subscription packages if none exist
        if (SubscriptionPackage::count() === 0) {
            SubscriptionPackage::create([
                'name' => 'Starter Trial',
                'description' => 'For single-branch hardware stores getting started with digital POS and inventory.',
                'price' => 0.00,
                'duration_days' => 30,
                'max_users' => 2,
                'max_branches' => 1,
                'features' => ['pos', 'inventory', 'basic_reports'],
                'is_active' => true,
            ]);

            SubscriptionPackage::create([
                'name' => 'Business Standard',
                'description' => 'Full sales, multi-branch inventory, expenses and manufacturing BOM for growing factories.',
                'price' => 150000.00,
                'duration_days' => 30,
                'max_users' => 10,
                'max_branches' => 3,
                'features' => ['pos', 'inventory', 'manufacturing', 'expenses', 'full_reports', 'multi_branch'],
                'is_active' => true,
            ]);

            SubscriptionPackage::create([
                'name' => 'Enterprise Annual',
                'description' => 'Unlimited users and branches with priority auditing, advanced approvals and custom targets.',
                'price' => 1200000.00,
                'duration_days' => 365,
                'max_users' => null,
                'max_branches' => null,
                'features' => ['all_features', 'unlimited_branches', 'unlimited_users', 'custom_targets', 'audit_logs'],
                'is_active' => true,
            ]);
        }
    }
}
