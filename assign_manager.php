<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$manager = Role::firstOrCreate(['name' => 'Manager']);
$managerPerms = [
    'view sales', 'create sales', 'edit sales', 'delete sales',
    'view inventory', 'adjust inventory',
    'view products', 'create products', 'edit products',
    'view purchases', 'create purchases', 'edit purchases',
    'view recipes', 'create recipes', 'view production', 'create production',
    'view profit', 'view stock value', 'view report'
];

foreach($managerPerms as $p) {
    $perm = Permission::where('name', $p)->first();
    if($perm) {
        $manager->givePermissionTo($perm);
    }
}

echo "Assigned permissions to Manager role.";
