<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$cashier = Role::firstOrCreate(['name' => 'Cashier']);
$cashierPerms = [
    'view sales', 'create sales', 'edit sales', 
    'view products', 'view inventory'
];

foreach($cashierPerms as $p) {
    $perm = Permission::where('name', $p)->first();
    if($perm) {
        $cashier->givePermissionTo($perm);
    }
}

echo "Assigned permissions to Cashier role.";
