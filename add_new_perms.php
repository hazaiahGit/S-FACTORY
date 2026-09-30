<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$newPerms = ['view profit', 'view stock value', 'view report'];

foreach ($newPerms as $perm) {
    Permission::firstOrCreate(['name' => $perm]);
}

$superAdmin = Role::where('name', 'Super Admin')->first();
if ($superAdmin) {
    $superAdmin->givePermissionTo(Permission::all());
}
echo "Done";
