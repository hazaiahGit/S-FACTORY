<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Permissions & Roles
        $this->call(PermissionSeeder::class);

        // 2. Seed System Administrator & Subscription Packages
        $this->call(SystemAdminSeeder::class);

        // 3. Create Default Business & Branches (if not already existing)
        $business = Business::firstOrCreate(
            ['slug' => 'mega-hardware'],
            [
                'name' => 'Mega Hardware & Steel Factory',
                'code' => 'MHSF',
                'business_type' => 'both',
                'currency' => 'TZS',
                'currency_symbol' => 'TZS',
                'locale' => 'en',
                'timezone' => 'Africa/Dar_es_Salaam',
            ]
        );

        $mainBranch = Branch::firstOrCreate(
            ['business_id' => $business->id, 'code' => 'HQ-01'],
            [
                'name' => 'Main HQ & Hardware Store',
                'type' => 'head_office',
                'is_main' => true,
            ]
        );

        $factoryBranch = Branch::firstOrCreate(
            ['business_id' => $business->id, 'code' => 'FAC-01'],
            [
                'name' => 'Steel Production Factory',
                'type' => 'factory',
                'is_main' => false,
            ]
        );

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);

        // Cashier user
        $cashier = User::firstOrCreate(
            ['email' => 'cashier@sfactory.com'],
            [
                'name' => 'John Cashier',
                'password' => Hash::make('password'),
                'business_id' => $business->id,
                'branch_id' => $mainBranch->id,
                'job_title' => 'Senior Cashier',
            ]
        );
        $cashier->assignRole($cashierRole);

        // Seed tenant catalog taxonomies
        \App\Services\TenantCatalogSeederService::seedTenantDefaults($business);

        echo "✅ Fresh System Seeded Successfully! (Superadmin, Tenant, and Demo accounts ready)\n";
    }
}
