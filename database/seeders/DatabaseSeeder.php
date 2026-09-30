<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Branch;
use App\Models\User;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Business
        $business = Business::create([
            'name' => 'Mega Hardware & Steel Factory',
            'slug' => 'mega-hardware',
            'code' => 'MHSF',
            'business_type' => 'both',
            'currency' => 'TZS',
            'currency_symbol' => 'TZS',
            'locale' => 'en',
            'timezone' => 'Africa/Dar_es_Salaam',
        ]);

        // 2. Create Branches
        $mainBranch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main HQ & Hardware Store',
            'code' => 'HQ-01',
            'type' => 'head_office',
            'is_main' => true,
        ]);

        $factoryBranch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Steel Production Factory',
            'code' => 'FAC-01',
            'type' => 'factory',
            'is_main' => false,
        ]);

        // 3. Create Roles & Permissions
        $adminRole = Role::create(['name' => 'Super Admin']);
        $managerRole = Role::create(['name' => 'Manager']);
        $cashierRole = Role::create(['name' => 'Cashier']);

        // 4. Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@sfactory.com',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
            'branch_id' => $mainBranch->id,
            'job_title' => 'System Administrator',
        ]);
        $admin->assignRole($adminRole);
        $admin->branches()->attach([$mainBranch->id, $factoryBranch->id]);

        $cashier = User::create([
            'name' => 'John Cashier',
            'email' => 'cashier@sfactory.com',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
            'branch_id' => $mainBranch->id,
            'job_title' => 'Senior Cashier',
        ]);
        $cashier->assignRole($cashierRole);

        // 5. Default Settings or setup can go here
        echo "✅ Fresh System Seeded Successfully! (No dummy data)\n";
    }
}
