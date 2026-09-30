<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use App\Models\User;

echo "=== ROLES & PERMISSIONS ===\n";
foreach(Role::with('permissions')->get() as $role) {
    echo "\n[{$role->name}] ({$role->permissions->count()} perms):\n";
    foreach($role->permissions->pluck('name') as $p) {
        echo "  - $p\n";
    }
}

echo "\n=== CASHIER USERS ===\n";
foreach(User::role('Cashier')->get() as $u) {
    echo "User: {$u->name} | Role perms: " . $u->getAllPermissions()->pluck('name')->implode(', ') . "\n";
}
