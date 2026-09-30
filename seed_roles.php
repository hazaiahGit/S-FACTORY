<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$allPerms = Permission::all();

// Super Admin: all permissions
$sa = Role::firstOrCreate(['name' => 'Super Admin']);
$sa->syncPermissions($allPerms);

// Manager: most permissions except user/role management
$manager = Role::firstOrCreate(['name' => 'Manager']);
$managerPerms = [
    'view sales', 'create sales', 'edit sales', 'delete sales',
    'view inventory', 'adjust inventory',
    'view products', 'create products', 'edit products', 'delete products',
    'view purchases', 'create purchases', 'edit purchases', 'delete purchases',
    'view recipes', 'create recipes', 'view production', 'create production',
    'view profit', 'view stock value', 'view report'
];
$manager->syncPermissions(Permission::whereIn('name', $managerPerms)->get());

// Cashier: limited to sales
$cashier = Role::firstOrCreate(['name' => 'Cashier']);
$cashierPerms = ['view sales', 'create sales', 'edit sales', 'view products', 'view inventory'];
$cashier->syncPermissions(Permission::whereIn('name', $cashierPerms)->get());

echo "Done - Roles: " . Role::count() . ", Super Admin perms: " . $sa->permissions()->count() . ", Manager perms: " . $manager->permissions()->count() . ", Cashier perms: " . $cashier->permissions()->count();
