<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'POS & Sales' => ['view sales', 'create sales', 'edit sales', 'delete sales'],
            'Inventory' => ['view inventory', 'adjust inventory'],
            'Products' => ['view products', 'create products', 'edit products', 'delete products'],
            'Purchases' => ['view purchases', 'create purchases', 'edit purchases', 'delete purchases'],
            'Manufacturing' => ['view recipes', 'create recipes', 'view production', 'create production'],
            'Users & Roles' => ['manage users', 'manage roles'],
            'Settings' => ['manage settings'],
        ];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(['name' => $perm]);
            }
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());
    }
}
