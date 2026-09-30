<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::find(2);
echo "User 2 Direct Permissions: " . json_encode($u->permissions->pluck('name')) . "\n";
echo "User 2 All Permissions (incl Role): " . json_encode($u->getAllPermissions()->pluck('name')) . "\n";
